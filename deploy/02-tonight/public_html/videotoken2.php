<?php
/**
 * صدور توکن موقت برای پخش ویدیو — نسخه‌ی تحمل‌پذیر در برابر قطعی Supabase
 * خروجی دقیقاً مثل videotoken.php است.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function out($a, $c = 200) {
    http_response_code($c);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    if (function_exists('mm_finish')) mm_finish();
    exit;
}

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/mm-cache.php';

$unavailable = ['ok'=>false, 'unavailable'=>true, 'error'=>'سرویس موقتاً در دسترس نیست. چند لحظه بعد دوباره تلاش کن.'];

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$videoId = isset($_GET['v']) ? trim($_GET['v']) : '';
$courseId = isset($_GET['c']) ? trim($_GET['c']) : '';

$sessUser = mm_session_user($token);
if ($sessUser === false) out($unavailable, 503);
if (!$sessUser) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);
if ($videoId === '') out(['ok'=>false,'error'=>'ویدیو مشخص نشده'], 400);

$video = mm_row('course_videos', $videoId, 'id,course_id,file_name');
if ($video === false) $video = mm_video_from_cache($courseId, $videoId) ?: false;   // موقع قطعی: از لیست کش‌شده‌ی دوره
if ($video === false) out($unavailable, 503);
if (!$video) out(['ok'=>false,'error'=>'ویدیو یافت نشد'], 404);

$targetCourse = $courseId !== '' ? $courseId : $video['course_id'];
$list = mm_access_list($sessUser['id'], $targetCourse);
if ($list === false) out($unavailable, 503);
if (!mm_list_has($list, $targetCourse)) out(['ok'=>false,'error'=>'شما به این دوره دسترسی ندارید'], 403);

// توکن ۸ ساعته — بدون قفل IP
$payload = [
    'f' => $video['file_name'],
    'u' => $sessUser['id'],
    'exp' => time() + 28800,
];
$b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
$sig = hash_hmac('sha256', $b64, SERVICE_KEY);

out(['ok'=>true, 'token'=>$b64.'.'.$sig, 'expires_in'=>28800]);
