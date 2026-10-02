<?php
/**
 * صدور توکن دانلود امن برای فایل پیوست
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function out($a, $c = 200) { http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

require_once __DIR__ . '/api/config.php';

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$attachId = isset($_GET['a']) ? trim($_GET['a']) : '';

// به‌جای اعتماد صرف به شماره موبایل، نشست ورود رو تأیید می‌کنیم
$user = sb_require_session($token);
if (!$user) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);
if ($attachId === '') out(['ok'=>false,'error'=>'فایل مشخص نشده'], 400);

// پیوست → ویدیو → دوره
$a = sb_request('course_attachments?id=eq.'.rawurlencode($attachId).'&select=id,title,file_name,video_id');
$att = isset($a['data'][0]) ? $a['data'][0] : null;
if (!$att) out(['ok'=>false,'error'=>'فایل یافت نشد'], 404);

$v = sb_request('course_videos?id=eq.'.rawurlencode($att['video_id']).'&select=course_id');
$video = isset($v['data'][0]) ? $v['data'][0] : null;
if (!$video) out(['ok'=>false,'error'=>'ویدیو یافت نشد'], 404);

$acc = sb_request('course_access?user_id=eq.'.rawurlencode($user['id']).'&course_id=eq.'.rawurlencode($video['course_id']).'&is_active=eq.true&select=id');
if (empty($acc['data'])) out(['ok'=>false,'error'=>'شما به این دوره دسترسی ندارید'], 403);

$payload = [
    'f' => $att['file_name'],
    'title' => $att['title'],
    'exp' => time() + 3600,
];
$b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
$sig = hash_hmac('sha256', $b64, SERVICE_KEY);

out(['ok'=>true, 'token'=>$b64.'.'.$sig]);
