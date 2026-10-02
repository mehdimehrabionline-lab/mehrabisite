<?php
// دوره‌های خریداری‌شده کاربر — نسخه‌ی سریع و تحمل‌پذیر در برابر قطعی Supabase
// خروجی دقیقاً مثل mycourses.php است؛ فقط از کش کوتاه‌مدت/قدیمی استفاده می‌کند و درخواست‌ها را هم‌زمان می‌زند.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

function out($a, $c = 200) {
    http_response_code($c);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    if (function_exists('mm_finish')) mm_finish();
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
require_once __DIR__ . '/api/mm-cache.php';

function is_list_array($a){ return is_array($a) && (empty($a) || array_keys($a) === range(0, count($a)-1)); }

// توکن امضاشده‌ی پخش — دقیقاً همون فرمتی که stream.php می‌خونه
function make_stream_token($fileName, $userId) {
    $payload = ['f' => $fileName, 'u' => $userId, 'exp' => time() + 28800];
    $b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    return $b64 . '.' . hash_hmac('sha256', $b64, SERVICE_KEY);
}

$unavailable = ['ok'=>false, 'unavailable'=>true, 'error'=>'سرویس موقتاً در دسترس نیست. چند لحظه بعد دوباره تلاش کن.'];

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$courseId = isset($_GET['c']) ? trim($_GET['c']) : '';

$user = mm_session_user($token);
if ($user === false) out($unavailable, 503);
if (!$user) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);

// ── لیست کلی دوره‌ها (برای داشبورد) ──
if ($courseId === '') {
    $list = mm_access_list($user['id']);
    if ($list === false) out($unavailable, 503);
    if (!$list) out(['ok'=>true, 'courses'=>[]]);

    $ids = [];
    $grantMap = [];
    foreach ($list as $a) {
        $ids[] = $a['course_id'];
        $grantMap[$a['course_id']] = isset($a['granted_at']) ? $a['granted_at'] : null;
    }

    // اطلاعات دوره‌ها از کش محلی همین هاست؛ فقط برای دوره‌ای که اونجا نبود از Supabase پرسیده می‌شه
    $found = [];
    $cf = __DIR__ . '/api/cache/course_full.json';
    if (is_file($cf)) {
        $rows = json_decode((string)@file_get_contents($cf), true);
        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (is_array($r) && isset($r['id']) && isset($grantMap[$r['id']])) $found[$r['id']] = $r;
            }
        }
    }

    $missing = array_values(array_diff($ids, array_keys($found)));
    if ($missing) {
        $c = mm_sb_one('courses?id=in.('.implode(',', array_map('rawurlencode', $missing)).')&select=id,title,cover_url,level,duration', true);
        if (mm_ok_list($c)) {
            foreach ($c['data'] as $row) {
                if (isset($row['id'])) $found[$row['id']] = $row;
            }
        }
    }

    $courses = [];
    foreach ($ids as $cid) {
        if (!isset($found[$cid])) continue;
        $r = $found[$cid];
        $courses[] = [
            'id'         => $cid,
            'title'      => isset($r['title']) ? $r['title'] : '',
            'cover_url'  => isset($r['cover_url']) ? $r['cover_url'] : null,
            'level'      => isset($r['level']) ? $r['level'] : null,
            'duration'   => isset($r['duration']) ? $r['duration'] : null,
            'granted_at' => $grantMap[$cid],
        ];
    }
    out(['ok'=>true, 'courses'=>$courses]);
}

// ── ویدیوهای یک دوره ──
$b = mm_course_bundle($user['id'], $courseId);
if ($b['status'] !== 'ok') out($unavailable, 503);
if (!$b['has']) out(['ok'=>false,'error'=>'شما به این دوره دسترسی ندارید']);
$videos = $b['videos'];

// توکن پخش همه‌ی جلسات همین‌جا صادر می‌شه ← کلیک کاربر = شروع فوری
$outVideos = [];
foreach ($videos as $vid) {
    $atts = isset($vid['course_attachments']) && is_array($vid['course_attachments']) ? $vid['course_attachments'] : [];
    usort($atts, function($a,$b){ return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0); });
    $outVideos[] = [
        'id' => $vid['id'],
        'title' => $vid['title'],
        'description' => $vid['description'] ?? '',
        'duration' => $vid['duration'] ?? '',
        'attachments' => array_map(function($a){ return ['id'=>$a['id'],'title'=>$a['title'],'file_size'=>$a['file_size'] ?? null]; }, $atts),
        'token' => !empty($vid['file_name']) ? make_stream_token($vid['file_name'], $user['id']) : null,
        'type' => (!empty($vid['file_name']) && preg_match('/\.m3u8$/i', $vid['file_name'])) ? 'hls' : 'mp4',
    ];
}

out([
    'ok' => true,
    'videos' => $outVideos,
    'token_ttl' => 28800,
    'watermark' => isset($user['phone']) ? $user['phone'] : '',
    'user_name' => isset($user['full_name']) ? $user['full_name'] : '',
]);
