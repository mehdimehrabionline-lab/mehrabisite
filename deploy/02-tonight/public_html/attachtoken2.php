<?php
/**
 * صدور توکن دانلود امن برای فایل پیوست — نسخه‌ی تحمل‌پذیر در برابر قطعی Supabase
 * خروجی دقیقاً مثل attachtoken.php است.
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
$attachId = isset($_GET['a']) ? trim($_GET['a']) : '';

$user = mm_session_user($token);
if ($user === false) out($unavailable, 503);
if (!$user) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);
if ($attachId === '') out(['ok'=>false,'error'=>'فایل مشخص نشده'], 400);

// پیوست → ویدیو → دوره
$att = mm_row('course_attachments', $attachId, 'id,title,file_name,video_id');
$cachedCourse = null;
if ($att === false) {   // موقع قطعی: از لیست کش‌شده‌ی ویدیوهای دوره‌ها
    $f = mm_attachment_from_cache($attachId);
    if ($f) { $att = $f['att']; $cachedCourse = $f['course_id']; }
}
if ($att === false) out($unavailable, 503);
if (!$att) out(['ok'=>false,'error'=>'فایل یافت نشد'], 404);

if ($cachedCourse !== null) {
    $video = ['id' => $att['video_id'], 'course_id' => $cachedCourse];
} else {
    $video = mm_row('course_videos', $att['video_id'], 'id,course_id');
    if ($video === false) out($unavailable, 503);
    if (!$video) out(['ok'=>false,'error'=>'ویدیو یافت نشد'], 404);
}

$list = mm_access_list($user['id'], $video['course_id']);
if ($list === false) out($unavailable, 503);
if (!mm_list_has($list, $video['course_id'])) out(['ok'=>false,'error'=>'شما به این دوره دسترسی ندارید'], 403);

$payload = [
    'f' => $att['file_name'],
    'title' => $att['title'],
    'exp' => time() + 3600,
];
$b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
$sig = hash_hmac('sha256', $b64, SERVICE_KEY);

out(['ok'=>true, 'token'=>$b64.'.'.$sig]);
