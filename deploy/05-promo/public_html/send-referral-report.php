<?php
/**
 * ارسال پیامک گزارش به یک معرف — فقط ادمین (توکن Supabase Auth)
 *   POST {"code":"ABC"}  → پیامک الگویی (کلید referral_report_sms_template در site_content)
 *   پارامترهای الگو: NAME, COUNT (تعداد فروش), PENDING (پورسانت پرداخت‌نشده، تومان), PAID (پرداخت‌شده، تومان)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
function out($a, $c = 200) { http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/promo.php';

$auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$token = trim(str_replace('Bearer ', '', $auth));
if (!$token) out(['ok' => false, 'error' => 'دسترسی غیرمجاز'], 401);
$ch = curl_init(SUPABASE_URL . '/auth/v1/user');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['apikey: ' . getAnonKey(), 'Authorization: Bearer ' . $token]]);
$u = json_decode(curl_exec($ch), true); curl_close($ch);
if (empty($u['id'])) out(['ok' => false, 'error' => 'توکن نامعتبر'], 401);

$body = json_decode(file_get_contents('php://input'), true);
$code = mm_promo_normalize(isset($body['code']) ? $body['code'] : '');
$r = sb_request('promo_codes?code=eq.' . rawurlencode($code) . '&kind=eq.referral&select=*&limit=1');
$p = isset($r['data'][0]) ? $r['data'][0] : null;
if (!$p) out(['ok' => false, 'error' => 'کد معرف پیدا نشد'], 404);
if (empty($p['referrer_phone'])) out(['ok' => false, 'error' => 'شماره‌ی معرف ثبت نشده'], 400);
$tpl = mm_setting('referral_report_sms_template');
if ($tpl === '' || !ctype_digit($tpl)) out(['ok' => false, 'error' => 'شناسه‌ی الگوی پیامک گزارش در تنظیمات کدها ثبت نشده'], 400);

$rows = sb_request('promo_redemptions?code=eq.' . rawurlencode($code) . '&select=commission_amount,commission_status&limit=5000');
$cnt = 0; $pend = 0; $paid = 0;
foreach ((isset($rows['data']) && is_array($rows['data']) ? $rows['data'] : []) as $x) {
    $cnt++; if ($x['commission_status'] === 'paid') $paid += (int)$x['commission_amount']; else $pend += (int)$x['commission_amount'];
}
$ok = mm_sms_template($p['referrer_phone'], $tpl, ['NAME' => $p['referrer_name'] ?: 'همکار', 'COUNT' => (string)$cnt, 'PENDING' => mm_promo_money($pend), 'PAID' => mm_promo_money($paid)]);
if (!$ok) out(['ok' => false, 'error' => 'ارسال پیامک ناموفق (الگو یا اعتبار SMS.ir را بررسی کن)'], 502);
out(['ok' => true, 'count' => $cnt, 'pending' => $pend, 'paid' => $paid]);
