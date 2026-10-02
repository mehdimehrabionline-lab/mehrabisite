<?php
/**
 * صدور توکن موقت برای پخش ویدیو — بهینه‌شده با درخواست موازی (سریع‌تر)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function out($a, $c = 200) {
    http_response_code($c);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/api/config.php';

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$videoId = isset($_GET['v']) ? trim($_GET['v']) : '';
$courseId = isset($_GET['c']) ? trim($_GET['c']) : '';

// به‌جای اعتماد صرف به شماره موبایل، نشست ورود رو تأیید می‌کنیم
$sessUser = sb_require_session($token);
if (!$sessUser) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);
if ($videoId === '') out(['ok'=>false,'error'=>'ویدیو مشخص نشده'], 400);

$video = null;
$v = sb_request('course_videos?id=eq.'.rawurlencode($videoId).'&select=id,course_id,file_name');
$video = isset($v['data'][0]) ? $v['data'][0] : null;
if (!$video) out(['ok'=>false,'error'=>'ویدیو یافت نشد'], 404);

$targetCourse = $courseId !== '' ? $courseId : $video['course_id'];
$acc = sb_request('course_access?user_id=eq.'.rawurlencode($sessUser['id']).'&course_id=eq.'.rawurlencode($targetCourse).'&is_active=eq.true&select=id');
if (empty($acc['data'])) out(['ok'=>false,'error'=>'شما به این دوره دسترسی ندارید'], 403);

// توکن ۸ ساعته — بدون قفل IP
$payload = [
    'f' => $video['file_name'],
    'u' => $sessUser['id'],
    'exp' => time() + 28800,
];
$b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
$sig = hash_hmac('sha256', $b64, SERVICE_KEY);

out(['ok'=>true, 'token'=>$b64.'.'.$sig, 'expires_in'=>28800]);
