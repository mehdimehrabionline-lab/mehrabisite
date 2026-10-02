<?php
/**
 * بررسی اینکه یک شماره‌ی موبایل قبلاً برای «حساب دیگری» ثبت شده یا نه — قبل از فرستادن پیامک تأیید
 *
 *   GET /check-phone.php?t=<توکن نشست>&p=09xxxxxxxxx
 *   → {"ok":true,"available":true|false}
 *
 * فقط برای کاربر واردشده (نشست معتبر) و با سقف تعداد درخواست؛ چیزی در دیتابیس تغییر نمی‌دهد.
 * (بررسی نهایی همچنان در set_phone سمت Supabase هست؛ این فقط جلوی پیامک بیهوده را می‌گیرد.)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

function out($a, $c = 200) {
    http_response_code($c);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/api/config.php';

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$phone = isset($_GET['p']) ? trim($_GET['p']) : '';

$user = sb_require_session($token);
if (!$user) out(['ok' => false, 'error' => 'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);
if (!preg_match('/^09[0-9]{9}$/', $phone)) out(['ok' => false, 'error' => 'شماره موبایل معتبر نیست'], 400);

// سقف: ۲۰ بررسی در هر ۱۰ دقیقه برای هر نشست (جلوی استفاده‌ی انبوه برای حدس زدن شماره‌ها)
$f = sys_get_temp_dir() . '/mm_cp_' . substr(hash('sha256', $token), 0, 16);
$now = time();
$hits = is_file($f) ? json_decode((string)@file_get_contents($f), true) : [];
if (!is_array($hits)) $hits = [];
$hits = array_values(array_filter($hits, function ($t) use ($now) { return $t > $now - 600; }));
if (count($hits) >= 20) out(['ok' => false, 'error' => 'تعداد تلاش‌ها زیاد است؛ چند دقیقه بعد دوباره امتحان کن'], 429);
$hits[] = $now;
@file_put_contents($f, json_encode($hits), LOCK_EX);

// شماره‌ی خودِ همین کاربر
if (!empty($user['phone']) && $user['phone'] === $phone) out(['ok' => true, 'available' => true, 'same' => true]);

$r = sb_request('academy_users?phone=eq.' . rawurlencode($phone) . '&select=id');
if (!isset($r['data']) || !is_array($r['data']) || (!empty($r['data']) && !isset($r['data'][0]))) {
    out(['ok' => false, 'error' => 'بررسی شماره ممکن نشد'], 502);   // صفحه در این حالت ادامه می‌دهد؛ set_phone دوباره چک می‌کند
}
$taken = false;
foreach ($r['data'] as $row) {
    if (isset($row['id']) && $row['id'] !== $user['id']) { $taken = true; break; }
}
out(['ok' => true, 'available' => !$taken]);
