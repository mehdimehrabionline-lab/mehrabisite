<?php
/**
 * کد تخفیف و کد معرف — کتابخانه‌ی مشترک (pay.php / verify.php / promo-check.php)
 * هرگز نباید جلوی پرداخت یا تأیید پرداخت را بگیرد: هر خطا در این فایل فقط «کد اعمال نمی‌شود».
 */
if (!function_exists('mm_promo_normalize')) {

function mm_promo_normalize($c) {
    $c = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string)$c)));
    return substr($c, 0, 32);
}

function mm_promo_money($n) { return number_format((int)$n); }

/**
 * بررسی و محاسبه‌ی کد
 * @return array ['ok'=>bool, 'error'=>string, 'promo'=>array, 'original'=>int, 'discount'=>int, 'final'=>int]
 */
function mm_promo_resolve($code, $courseId, $amount, $user) {
    $code = mm_promo_normalize($code);
    if ($code === '') return ['ok' => false, 'error' => 'کد را وارد کن'];
    $r = sb_request('promo_codes?code=eq.' . rawurlencode($code) . '&select=*&limit=1');
    if (!isset($r['data']) || !is_array($r['data'])) return ['ok' => false, 'error' => 'کد نامعتبر است'];
    $p = isset($r['data'][0]) ? $r['data'][0] : null;
    if (!$p || empty($p['is_active'])) return ['ok' => false, 'error' => 'کد نامعتبر است'];
    if (!empty($p['expires_at']) && strtotime($p['expires_at']) < time()) return ['ok' => false, 'error' => 'مهلت این کد تمام شده است'];
    if (!empty($p['course_id']) && $p['course_id'] !== $courseId) return ['ok' => false, 'error' => 'این کد برای این دوره معتبر نیست'];
    if ($p['max_uses'] !== null && (int)$p['used_count'] >= (int)$p['max_uses']) return ['ok' => false, 'error' => 'ظرفیت استفاده از این کد تمام شده است'];
    if ($p['kind'] === 'referral' && !empty($p['referrer_phone']) && !empty($user['phone']) && $p['referrer_phone'] === $user['phone']) {
        return ['ok' => false, 'error' => 'نمی‌توانی از کد معرف خودت استفاده کنی'];
    }
    $amount = (int)$amount;
    $v = (int)$p['discount_value'];
    $disc = $p['discount_type'] === 'percent' ? (int)floor($amount * min($v, 100) / 100) : $v;
    $disc = max(0, min($disc, $amount - 1000));          // مبلغ نهایی هیچ‌وقت زیر ۱۰۰۰ تومان نمی‌شود
    return ['ok' => true, 'promo' => $p, 'original' => $amount, 'discount' => $disc, 'final' => $amount - $disc];
}

/** ارسال پیامک الگویی SMS.ir؛ true اگر درخواست موفق بود */
function mm_sms_template($mobile, $templateId, $params) {
    if (!defined('SMSIR_KEY') || SMSIR_KEY === '' || !$mobile || !$templateId) return false;
    $pp = [];
    foreach ($params as $k => $v) $pp[] = ['name' => $k, 'value' => mb_substr((string)$v, 0, 25)];
    $body = json_encode(['mobile' => preg_replace('/^0/', '98', $mobile), 'templateId' => (int)$templateId, 'parameters' => $pp], JSON_UNESCAPED_UNICODE);
    $ch = curl_init((defined('MM_SMS_BASE') ? MM_SMS_BASE : 'https://api.sms.ir') . '/v1/send/verify');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-KEY: ' . SMSIR_KEY], CURLOPT_POSTFIELDS => $body]);
    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $code >= 200 && $code < 300;
}

function mm_setting($key) {
    $r = sb_request('site_content?key=eq.' . rawurlencode($key) . '&select=value&limit=1');
    return isset($r['data'][0]['value']) ? trim($r['data'][0]['value']) : '';
}

/**
 * بعد از پرداخت موفق صدا زده می‌شود (فقط یک‌بار برای هر پرداخت).
 * ثبت مصرف کد + پورسانت معرف + پیامک به معرف. خروجی: متن کوتاه برای پیام تلگرام یا ''.
 */
function mm_promo_on_paid($payment, $courseTitleShort) {
    $code = mm_promo_normalize(isset($payment['promo_code']) ? $payment['promo_code'] : '');
    if ($code === '') return '';
    $r = sb_request('promo_codes?code=eq.' . rawurlencode($code) . '&select=*&limit=1');
    $p = isset($r['data'][0]) ? $r['data'][0] : null;
    if (!$p) return '';
    $final = (int)$payment['amount'];
    $orig = isset($payment['original_amount']) && $payment['original_amount'] !== null ? (int)$payment['original_amount'] : $final;
    $disc = isset($payment['discount_amount']) ? (int)$payment['discount_amount'] : max(0, $orig - $final);
    $comm = 0;
    if ($p['kind'] === 'referral' && $p['commission_value'] !== null) {
        $cv = (int)$p['commission_value'];
        $comm = $p['commission_type'] === 'percent' ? (int)floor($final * min($cv, 100) / 100) : min($cv, $final);   // پورسانت از مبلغ «بعد از تخفیف»
    }
    $ins = sb_request('promo_redemptions', 'POST', [
        'payment_id' => $payment['id'], 'code_id' => $p['id'], 'code' => $code, 'kind' => $p['kind'],
        'referrer_name' => $p['referrer_name'], 'referrer_phone' => $p['referrer_phone'],
        'buyer_user_id' => $payment['user_id'], 'course_id' => $payment['course_id'],
        'original_amount' => $orig, 'discount_amount' => $disc, 'final_amount' => $final,
        'commission_amount' => $comm, 'commission_status' => 'pending',
    ]);
    if ($ins['code'] < 200 || $ins['code'] >= 300 || empty($ins['data'][0]['id'])) return '';   // قبلاً ثبت شده (تکراری) یا خطا
    $rid = $ins['data'][0]['id'];
    sb_request('promo_codes?id=eq.' . rawurlencode($p['id']), 'PATCH', ['used_count' => (int)$p['used_count'] + 1]);
    $line = "\n🎟 کد: $code" . ($disc ? ' (تخفیف ' . mm_promo_money($disc) . ' ت)' : '');
    if ($p['kind'] === 'referral') {
        $line .= "\n🤝 معرف: " . ($p['referrer_name'] ?: '-') . ' ' . $p['referrer_phone'] . "\n💸 پورسانت: " . mm_promo_money($comm) . ' تومان';
        $tpl = mm_setting('referral_sms_template');
        if ($tpl !== '' && ctype_digit($tpl) && !empty($p['referrer_phone']) && $comm > 0) {
            $ok = mm_sms_template($p['referrer_phone'], $tpl, ['NAME' => $p['referrer_name'] ?: 'همکار', 'COURSE' => $courseTitleShort, 'AMOUNT' => mm_promo_money($comm)]);
            if ($ok) sb_request('promo_redemptions?id=eq.' . rawurlencode($rid), 'PATCH', ['sms_sent' => true]);
        }
    }
    return $line;
}

}
