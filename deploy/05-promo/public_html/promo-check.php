<?php
/**
 * پیش‌نمایش کد تخفیف/معرف روی صفحه‌ی دوره (چیزی ثبت نمی‌کند)
 *   GET /promo-check.php?t=<توکن نشست>&c=<course_id>&code=ABC
 *   → {"ok":true,"original":..,"discount":..,"final":..,"kind":"referral|discount"}
 * سقف ۲۰ بررسی در هر ۱۰ دقیقه برای هر نشست (جلوی حدس زدن کدها).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
function out($a, $c = 200) { http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/promo.php';

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$courseId = isset($_GET['c']) ? trim($_GET['c']) : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';
$user = sb_require_session($token);
if (!$user) out(['ok' => false, 'error' => 'ابتدا وارد حساب کاربری شو'], 401);

$f = sys_get_temp_dir() . '/mm_pc_' . substr(hash('sha256', $token), 0, 16);
$now = time();
$hits = is_file($f) ? json_decode((string)@file_get_contents($f), true) : [];
if (!is_array($hits)) $hits = [];
$hits = array_values(array_filter($hits, function ($t) use ($now) { return $t > $now - 600; }));
if (count($hits) >= 20) out(['ok' => false, 'error' => 'تعداد تلاش‌ها زیاد است؛ چند دقیقه بعد دوباره امتحان کن'], 429);
$hits[] = $now; @file_put_contents($f, json_encode($hits), LOCK_EX);

$c = sb_request('courses?id=eq.' . rawurlencode($courseId) . '&select=id,price_amount');
$course = isset($c['data'][0]) ? $c['data'][0] : null;
if (!$course || (int)$course['price_amount'] < 1000) out(['ok' => false, 'error' => 'دوره پیدا نشد']);
$res = mm_promo_resolve($code, $courseId, (int)$course['price_amount'], $user);
if (!$res['ok']) out(['ok' => false, 'error' => $res['error']]);
out(['ok' => true, 'original' => $res['original'], 'discount' => $res['discount'], 'final' => $res['final'], 'kind' => $res['promo']['kind']]);
