<?php
// تأیید پرداخت — در ریشه سایت
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function out($a, $c = 200) {
    http_response_code($c);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(200);
        echo json_encode(['ok'=>false,'error'=>'FATAL: '.$e['message']], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/api/config.php';
if (is_file(__DIR__ . '/api/promo.php')) require_once __DIR__ . '/api/promo.php';

// نام دوره برای پیامک — SMS.ir حداکثر ۲۵ کاراکتر برای هر پارامتر قبول می‌کنه
if (!function_exists('sms_course_name')) {
function sms_course_name($title, $short = '') {
    $short = trim((string)$short);
    if ($short !== '') return mb_substr($short, 0, 25);
    $t = trim(preg_replace('/^دوره\s+/u', '', trim((string)$title)));
    if ($t === '') $t = 'دوره آموزشی';
    if (mb_strlen($t) <= 25) return $t;
    $cut = mb_substr($t, 0, 24);
    $sp = mb_strrpos($cut, ' ');
    if ($sp !== false && $sp > 10) $cut = mb_substr($cut, 0, $sp);
    return $cut . '…';
}
}


$trackId = isset($_GET['trackId']) ? trim($_GET['trackId']) : '';
if ($trackId === '') out(['ok'=>false,'error'=>'شناسه تراکنش نامعتبر']);

$p = sb_request('payments?track_id=eq.'.rawurlencode($trackId).'&select=*');
$payment = isset($p['data'][0]) ? $p['data'][0] : null;
if (!$payment) out(['ok'=>false,'error'=>'تراکنش یافت نشد']);

$c = sb_request('courses?id=eq.'.rawurlencode($payment['course_id']).'&select=title,sms_title');
if (!isset($c['data'][0]['title'])) $c = sb_request('courses?id=eq.'.rawurlencode($payment['course_id']).'&select=title');
$ctitle = isset($c['data'][0]['title']) ? $c['data'][0]['title'] : 'دوره';
$csms = isset($c['data'][0]['sms_title']) ? $c['data'][0]['sms_title'] : '';

if ($payment['status'] === 'paid') {
    out(['ok'=>true,'already'=>true,'course_id'=>$payment['course_id'],'course_title'=>$ctitle,'amount'=>$payment['amount'],'ref_number'=>$payment['ref_number']]);
}

$body = json_encode(['merchant'=>ZIBAL_MERCHANT, 'trackId'=>(int)$trackId]);
$ch = curl_init('https://gateway.zibal.ir/v1/verify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
$raw = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) out(['ok'=>false,'error'=>'اتصال به زیبال: '.$err]);

$z = json_decode($raw, true);
if (!is_array($z)) out(['ok'=>false,'error'=>'پاسخ نامعتبر زیبال','raw'=>substr($raw,0,200)]);

$result = isset($z['result']) ? (int)$z['result'] : 0;
if ($result !== 100 && $result !== 201) {
    sb_request('payments?id=eq.'.rawurlencode($payment['id']), 'PATCH', ['status'=>'failed']);
    out(['ok'=>false,'error'=>'پرداخت ناموفق (کد '.$result.')']);
}

// ثبت موفقیت
sb_request('payments?id=eq.'.rawurlencode($payment['id']), 'PATCH', [
    'status' => 'paid',
    'ref_number' => isset($z['refNumber']) ? (string)$z['refNumber'] : '',
    'card_number' => isset($z['cardNumber']) ? $z['cardNumber'] : null,
    'paid_at' => gmdate('c'),
]);

// اعطای دسترسی (اگه از قبل نبود)
$exist = sb_request('course_access?user_id=eq.'.rawurlencode($payment['user_id']).'&course_id=eq.'.rawurlencode($payment['course_id']).'&select=id');
if (empty($exist['data'])) {
    sb_request('course_access', 'POST', [
        'user_id' => $payment['user_id'],
        'course_id' => $payment['course_id'],
        'payment_id' => $payment['id'],
        'is_active' => true,
    ]);
}

// کد تخفیف/معرف: ثبت مصرف + پورسانت + پیامک به معرف (هرگز نباید تأیید پرداخت را خراب کند)
$promoNote = '';
if (!empty($payment['promo_code']) && function_exists('mm_promo_on_paid')) {
    try { $promoNote = mm_promo_on_paid($payment, sms_course_name($ctitle, $csms)); } catch (Throwable $e) { $promoNote = ''; }
}

// اطلاع به ادمین
$u = sb_request('academy_users?id=eq.'.rawurlencode($payment['user_id']).'&select=full_name,phone');
$uname = isset($u['data'][0]['full_name']) ? $u['data'][0]['full_name'] : 'نامشخص';
$uphone = isset($u['data'][0]['phone']) ? $u['data'][0]['phone'] : '';
$msg = "💰 <b>خرید موفق!</b>\n\n👤 $uname\n📱 $uphone\n📚 $ctitle\n💵 " . number_format($payment['amount']) . " تومان\n🔖 " . (isset($z['refNumber']) ? $z['refNumber'] : '-');
$msg .= $promoNote;

// پیامک به خریدار
$smsKey = '';
$cfgJs = dirname(__DIR__) . '/public_html/supabase-config.js';
if (defined('SMSIR_KEY')) $smsKey = SMSIR_KEY;

if ($uphone && defined('SMSIR_KEY') && SMSIR_KEY !== '') {
    $smsBody = json_encode([
        'mobile' => preg_replace('/^0/', '98', $uphone),
        'templateId' => 869916,
        'parameters' => [
            ['name' => 'ORDERID', 'value' => mb_substr((!empty($z['refNumber']) ? (string)$z['refNumber'] : (string)$trackId), 0, 25)],
        ],
    ], JSON_UNESCAPED_UNICODE);
    $chSms = curl_init('https://api.sms.ir/v1/send/verify');
    curl_setopt($chSms, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chSms, CURLOPT_POST, true);
    curl_setopt($chSms, CURLOPT_TIMEOUT, 15);
    curl_setopt($chSms, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chSms, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-API-KEY: ' . SMSIR_KEY]);
    curl_setopt($chSms, CURLOPT_POSTFIELDS, $smsBody);
    curl_exec($chSms);
    curl_close($chSms);
}

$ch2 = curl_init(SUPABASE_URL . '/functions/v1/notify-admin');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['text'=>$msg], JSON_UNESCAPED_UNICODE));
curl_exec($ch2);
curl_close($ch2);

out([
    'ok' => true,
    'course_id' => $payment['course_id'],
    'course_title' => $ctitle,
    'ref_number' => isset($z['refNumber']) ? $z['refNumber'] : '',
    'amount' => $payment['amount'],
]);
