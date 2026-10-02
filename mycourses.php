<?php
// دوره‌های خریداری‌شده کاربر — بهینه‌شده (کمترین تعداد درخواست به دیتابیس)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

function out($a, $c = 200) {
    http_response_code($c);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
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

function is_list_array($a){ return is_array($a) && (empty($a) || array_keys($a) === range(0, count($a)-1)); }

// توکن امضاشده‌ی پخش — دقیقاً همون فرمتی که stream.php می‌خونه
function make_stream_token($fileName, $userId) {
    $payload = ['f' => $fileName, 'u' => $userId, 'exp' => time() + 28800];
    $b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    return $b64 . '.' . hash_hmac('sha256', $b64, SERVICE_KEY);
}

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$courseId = isset($_GET['c']) ? trim($_GET['c']) : '';

// به‌جای اعتماد صرف به شماره موبایل، نشست ورود رو تأیید می‌کنیم
$user = sb_require_session($token);
if (!$user) out(['ok'=>false,'error'=>'نشست نامعتبر یا منقضی‌شده، لطفاً دوباره وارد شو'], 401);

$acc = sb_request('course_access?user_id=eq.'.rawurlencode($user['id']).'&is_active=eq.true&select=course_id,granted_at');
$list = isset($acc['data']) && is_list_array($acc['data']) ? $acc['data'] : [];

// ── لیست کلی دوره‌ها (برای داشبورد) ──
// اطلاعات دوره‌ها (اسم، عکس، سطح، مدت) از کش محلی همین هاست خونده می‌شه؛
// فقط برای دوره‌ای که اونجا نبود (مثلاً بعداً غیرفعال شده) از Supabase پرسیده می‌شه.
if ($courseId === '') {
    if (!$list) out(['ok'=>true, 'courses'=>[]]);
    $ids = [];
    $grantMap = [];
    foreach ($list as $a) {
        $ids[] = $a['course_id'];
        $grantMap[$a['course_id']] = isset($a['granted_at']) ? $a['granted_at'] : null;
    }

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
        $c = sb_request('courses?id=in.('.implode(',', array_map('rawurlencode', $missing)).')&select=id,title,cover_url,level,duration');
        if (isset($c['data']) && is_list_array($c['data'])) {
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
$has = false;
foreach ($list as $a) if ($a['course_id'] === $courseId) $has = true;
if (!$has) out(['ok'=>false,'error'=>'شما به این دوره دسترسی ندارید']);

// درخواست ۲: ویدیوها + فایل‌های پیوست همه با هم
$v = sb_request('course_videos?course_id=eq.'.rawurlencode($courseId)
    .'&select=id,title,description,duration,sort_order,file_name,course_attachments(id,title,file_size,sort_order)'
    .'&order=sort_order.asc');
$videos = isset($v['data']) && is_list_array($v['data']) ? $v['data'] : null;

if ($videos === null) {
    // حالت پشتیبان (اگه embed کار نکرد)
    $v = sb_request('course_videos?course_id=eq.'.rawurlencode($courseId).'&select=id,title,description,duration,sort_order,file_name&order=sort_order.asc');
    $videos = isset($v['data']) && is_list_array($v['data']) ? $v['data'] : [];
    if ($videos) {
        $vids = array_map(function($x){ return $x['id']; }, $videos);
        $at = sb_request('course_attachments?video_id=in.('.implode(',', $vids).')&select=id,title,file_size,sort_order,video_id&order=sort_order.asc');
        $byVid = [];
        if (isset($at['data']) && is_list_array($at['data'])) foreach ($at['data'] as $row) $byVid[$row['video_id']][] = $row;
        foreach ($videos as &$vv) $vv['course_attachments'] = isset($byVid[$vv['id']]) ? $byVid[$vv['id']] : [];
        unset($vv);
    }
}

// توکن پخش همه‌ی جلسات همین‌جا صادر می‌شه ← کلیک کاربر = شروع فوری، بدون رفت‌وبرگشت اضافه
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
