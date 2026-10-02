<?php
/**
 * مدیریت فایل‌های روی هاست + وضعیت سرور — فقط ادمین
 *   GET  ?action=list     → فهرست فایل‌های protected_videos و assets/intro + فضای دیسک
 *   POST action=delete    → file, kind=video|intro   (فقط فایلی که نامش امن باشد و وجود داشته باشد)
 *   GET  ?action=health   → وضعیت PHP، دیسک، کش و پوشه‌ها
 * پنل قبل از حذف، خودش مطمئن می‌شود فایل در هیچ دوره/جلسه/پیوستی استفاده نشده.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/api/config.php';

define('VID_DIR', dirname(__DIR__) . '/protected_videos');
define('INTRO_DIR', __DIR__ . '/assets/intro');

function out($a, $c = 200) { http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$token = trim(str_replace('Bearer ', '', $auth));
if (!$token) out(['ok'=>false,'error'=>'دسترسی غیرمجاز'], 401);
$ch = curl_init(SUPABASE_URL . '/auth/v1/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: '.getAnonKey(), 'Authorization: Bearer '.$token]);
$userRes = json_decode(curl_exec($ch), true);
curl_close($ch);
if (empty($userRes['id'])) out(['ok'=>false,'error'=>'توکن نامعتبر'], 401);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

function listDir($dir) {
    $r = [];
    if (!is_dir($dir)) return $r;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..' || $f[0] === '.') continue;
        $p = $dir . '/' . $f; if (!is_file($p)) continue;
        $r[] = ['name'=>$f, 'size'=>filesize($p), 'mtime'=>filemtime($p)];
    }
    usort($r, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
    return $r;
}

if ($action === 'list') {
    $tmp = 0;
    foreach (glob(VID_DIR . '/.tmp-*.part') ?: [] as $f) $tmp += filesize($f);
    out(['ok'=>true,
        'videos'=>listDir(VID_DIR), 'intro'=>listDir(INTRO_DIR),
        'tmp_bytes'=>$tmp,
        'disk_free'=>@disk_free_space(VID_DIR) ?: @disk_free_space(__DIR__), 'disk_total'=>@disk_total_space(VID_DIR) ?: @disk_total_space(__DIR__)]);
}

if ($action === 'delete') {
    $kind = isset($_POST['kind']) ? $_POST['kind'] : 'video';
    $file = isset($_POST['file']) ? $_POST['file'] : '';
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}$/', $file) || strpos($file, '..') !== false) out(['ok'=>false,'error'=>'نام فایل نامعتبر'], 400);
    $dir = $kind === 'intro' ? INTRO_DIR : VID_DIR;
    $p = $dir . '/' . $file;
    if (!is_file($p)) out(['ok'=>false,'error'=>'فایل پیدا نشد'], 404);
    if (!@unlink($p)) out(['ok'=>false,'error'=>'حذف ناموفق (دسترسی)'], 500);
    out(['ok'=>true]);
}

if ($action === 'health') {
    $caches = [];
    $cdir = __DIR__ . '/api/cache';
    foreach (['courses','course_full','projects','posts','site_content','testimonials','faqs'] as $t) {
        $f = $cdir . '/' . $t . '.json';
        $caches[$t] = is_file($f) ? time() - filemtime($f) : null;
    }
    $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
    out(['ok'=>true,
        'php'=>PHP_VERSION, 'server'=>isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
        'memory_limit'=>ini_get('memory_limit'), 'upload_max'=>ini_get('upload_max_filesize'), 'post_max'=>ini_get('post_max_size'),
        'max_exec'=>ini_get('max_execution_time'),
        'disk_free'=>@disk_free_space(__DIR__), 'disk_total'=>@disk_total_space(__DIR__),
        'video_dir_ok'=>is_dir(VID_DIR) && is_writable(VID_DIR), 'intro_dir_ok'=>is_dir(INTRO_DIR) && is_writable(INTRO_DIR),
        'cache_dir_ok'=>is_dir($cdir) && is_writable($cdir), 'cache_age'=>$caches, 'load'=>$load,
        'video_count'=>count(listDir(VID_DIR)), 'time'=>time()]);
}

out(['ok'=>false,'error'=>'عملیات نامعتبر'], 400);
