<?php
/**
 * ثبت نظر توسط کاربر — فقط برای کسایی که حداقل یه دوره خریدن
 * همیشه به‌صورت تأیید‌نشده ثبت می‌شه تا ادمین از پنل تأییدش کنه
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/api/config.php';

function out($a, $c = 200) { http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) out(['ok'=>false,'error'=>'داده نامعتبر'], 400);

$token = isset($body['token']) ? trim($body['token']) : '';
$courseId = isset($body['course_id']) ? trim($body['course_id']) : '';
$content = isset($body['content']) ? trim($body['content']) : '';
$rating = isset($body['rating']) ? (int)$body['rating'] : 5;

// به‌جای اعتماد صرف به شماره موبایل، نشست ورود رو تأیید می‌کنیم
$user = sb_require_session($token);
if (!$user) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);
$phone = isset($user['phone']) ? $user['phone'] : '';
if ($courseId === '') out(['ok'=>false,'error'=>'دوره مشخص نشده'], 400);
if (mb_strlen($content) < 10) out(['ok'=>false,'error'=>'نظرت رو کمی کامل‌تر بنویس'], 400);
if ($rating < 1 || $rating > 5) $rating = 5;

// بررسی این‌که واقعاً این دوره رو خریده
$acc = sb_request('course_access?user_id=eq.'.rawurlencode($user['id']).'&course_id=eq.'.rawurlencode($courseId).'&is_active=eq.true&select=id');
if (empty($acc['data'])) out(['ok'=>false,'error'=>'شما این دوره رو نخریدی'], 403);

// اسم دوره برای نمایش
$c = sb_request('courses?id=eq.'.rawurlencode($courseId).'&select=title');
$courseTitle = isset($c['data'][0]['title']) ? $c['data'][0]['title'] : '';

$name = !empty($user['full_name']) ? $user['full_name'] : 'دانشجوی آکادمی';

$res = sb_request('testimonials', 'POST', [
    'name' => $name,
    'course' => $courseTitle,
    'content' => $content,
    'rating' => $rating,
    'is_published' => false,
    'user_phone' => $phone,
]);

if (empty($res['data'])) out(['ok'=>false,'error'=>'ثبت نظر ناموفق بود'], 500);

out(['ok'=>true, 'message'=>'نظرت ثبت شد و بعد از بررسی نمایش داده می‌شه']);
