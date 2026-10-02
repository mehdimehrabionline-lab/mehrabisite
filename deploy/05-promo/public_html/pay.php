<?php
// مسیر پرداخت — در ریشه سایت (نه داخل api) تا Cloudflare مسدودش نکنه
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

// پارامترها از GET می‌آیند (نه POST) تا مشکل CORS و WAF نباشد
$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$courseId = isset($_GET['c']) ? trim($_GET['c']) : '';

if ($courseId === '') out(['ok'=>false,'error'=>'دوره مشخص نشده']);

// به‌جای اعتماد صرف به شماره موبایل، نشست ورود رو تأیید می‌کنیم
$user = sb_require_session($token);
if (!$user) out(['ok'=>false,'error'=>'ابتدا وارد حساب کاربری شوید']);
if (empty($user['full_name']) || empty($user['email'])) out(['ok'=>false,'error'=>'ابتدا پروفایل خود را تکمیل کنید','need_profile'=>true]);
if (empty($user['phone']) || !preg_match('/^09[0-9]{9}$/', $user['phone'])) out(['ok'=>false,'error'=>'ابتدا شماره موبایل خود را تکمیل کنید']);

$c = sb_request('courses?id=eq.'.rawurlencode($courseId).'&select=id,title,price_amount,is_active');
$course = isset($c['data'][0]) ? $c['data'][0] : null;
if (!$course) out(['ok'=>false,'error'=>'دوره یافت نشد']);

$amount = (int)(isset($course['price_amount']) ? $course['price_amount'] : 0);
if ($amount < 1000) out(['ok'=>false,'error'=>'قیمت دوره تنظیم نشده']);

$acc = sb_request('course_access?user_id=eq.'.rawurlencode($user['id']).'&course_id=eq.'.rawurlencode($courseId).'&is_active=eq.true&select=id');
if (!empty($acc['data'])) out(['ok'=>false,'error'=>'قبلاً خریداری شده','already_owned'=>true]);

// کد تخفیف / کد معرف (اختیاری) — مبلغ نهایی همین‌جا در سرور حساب می‌شود، نه در مرورگر
$payRow = [
    'user_id' => $user['id'],
    'course_id' => $courseId,
    'amount' => $amount,
    'status' => 'pending',
];
$promoCode = isset($_GET['code']) && function_exists('mm_promo_normalize') ? mm_promo_normalize($_GET['code']) : '';
if ($promoCode !== '') {
    $pr = mm_promo_resolve($promoCode, $courseId, $amount, $user);
    if (!$pr['ok']) out(['ok'=>false,'error'=>$pr['error'],'promo_error'=>true]);
    $payRow['amount'] = $pr['final'];
    $payRow['promo_code'] = $promoCode;
    $payRow['original_amount'] = $pr['original'];
    $payRow['discount_amount'] = $pr['discount'];
    $amount = $pr['final'];
}
$pay = sb_request('payments', 'POST', $payRow);
$pid = isset($pay['data'][0]['id']) ? $pay['data'][0]['id'] : null;
if (!$pid) out(['ok'=>false,'error'=>'ثبت تراکنش ناموفق: '.$pay['code']]);

$body = json_encode([
    'merchant' => ZIBAL_MERCHANT,
    'amount' => $amount * 10,
    'callbackUrl' => ZIBAL_CALLBACK,
    'description' => mb_substr($course['title'], 0, 50),
    'orderId' => $pid,
    'mobile' => $user['phone'],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init('https://gateway.zibal.ir/v1/request');
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

$r = isset($z['result']) ? (int)$z['result'] : 0;
if ($r !== 100) {
    $m = [102=>'مرچنت یافت نشد',103=>'مرچنت غیرفعال',104=>'مرچنت نامعتبر',105=>'مبلغ کم',106=>'آدرس بازگشت نامعتبر',113=>'مبلغ زیاد'];
    out(['ok'=>false,'error'=>'زیبال ('.$r.'): '.(isset($m[$r])?$m[$r]:(isset($z['message'])?$z['message']:'خطای نامشخص'))]);
}

sb_request('payments?id=eq.'.rawurlencode($pid), 'PATCH', ['track_id'=>(string)$z['trackId']]);
out(['ok'=>true,'redirect'=>'https://gateway.zibal.ir/start/'.$z['trackId']]);
