<?php
/**
 * پخش امن ویدیو — نسخه‌ی بهینه‌شده برای حداکثر سرعت
 */
require_once __DIR__ . '/api/config.php';

define('VIDEO_DIR', dirname(__DIR__) . '/protected_videos');

// خاموش کردن هر لایه‌ای که می‌تونه سرعت رو کم کنه
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
@ini_set('output_handler', '');
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
while (ob_get_level()) ob_end_clean();

// ── اعتبارسنجی توکن ──
$token = isset($_GET['t']) ? $_GET['t'] : '';
if (!$token) { http_response_code(403); exit('Forbidden'); }

$parts = explode('.', $token);
if (count($parts) !== 2) { http_response_code(403); exit('Invalid'); }
if (!hash_equals(hash_hmac('sha256', $parts[0], SERVICE_KEY), $parts[1])) { http_response_code(403); exit('Bad signature'); }

$payload = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) { http_response_code(403); exit('Expired'); }

if (isset($payload['ip'])) {
    $clientIp = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
    if ($payload['ip'] !== $clientIp) { http_response_code(403); exit('IP mismatch'); }
}

$file = isset($payload['f']) ? basename($payload['f']) : '';
$path = VIDEO_DIR . '/' . $file;
if (!$file || !file_exists($path)) { http_response_code(404); exit('Not found'); }

// جلوگیری از هات‌لینک
$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if ($ref && stripos($ref, 'mehdimehrabi.ir') === false) { http_response_code(403); exit('Hotlink denied'); }

// ── فقط پخش‌کننده‌ی خود مرورگر (تگ video) ──
// مرورگرهای امروزی با هر درخواست پخش ویدیو هدر Sec-Fetch-Dest: video می‌فرستن.
// دانلود منیجرها (IDM و ...)، wget و curl ساده، افزونه‌های دانلود و باز کردن مستقیم آدرس توی یه تب جدید این هدر رو ندارن و رد می‌شن.
// (برای خاموش کردن سریع در صورت مشکل: مقدار زیر رو false کن)
define('MM_STRICT_FETCH', true);

function mm_is_browser_media_request() {
    $dest = isset($_SERVER['HTTP_SEC_FETCH_DEST']) ? strtolower(trim($_SERVER['HTTP_SEC_FETCH_DEST'])) : '';
    $site = isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? strtolower(trim($_SERVER['HTTP_SEC_FETCH_SITE'])) : '';
    $ua   = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    // پخش‌کننده‌ی داخلی اپل (آیفون/آیپد/مک) همیشه مجاز است، هر هدری که فرستاده باشد
    if (stripos($ua, 'AppleCoreMedia') !== false) return true;

    if ($dest !== '') {
        // مرورگر مدرن: باید از تگ video/audio بیاد (نه باز کردن مستقیم، نه fetch/افزونه)
        if ($dest !== 'video' && $dest !== 'audio') return false;
        // و نه از یه سایت دیگه
        if ($site === 'cross-site') return false;
        return true;
    }

    // هدر نیومده → فقط پخش‌کننده‌های خود اپل (که این هدر رو نمی‌فرستن) مجازن
    if (stripos($ua, 'AppleCoreMedia') !== false) return true;        // سافاری آیفون/آیپد/مک
    if (preg_match('/(iPhone|iPad|iPod)/i', $ua)) return true;         // iOS (همه‌ی مرورگرهای آیفون از WebKit استفاده می‌کنن)
    if (stripos($ua, 'Safari/') !== false && !preg_match('/(Chrome|Chromium|Edg|OPR|Firefox)\//i', $ua)) return true; // سافاری قدیمی مک
    return false;
}

if (MM_STRICT_FETCH && !mm_is_browser_media_request()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    exit('این ویدیو فقط داخل پلیر سایت قابل پخشه.');
}

// ── آماده‌سازی ──
$size = filesize($path);
$mtime = filemtime($path);
$etag = '"' . md5($file . $size . $mtime) . '"';
$start = 0;
$end = $size - 1;
$isRange = false;
$openEnded = false;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    $isRange = true;
    $start = (int)$m[1];
    $openEnded = ($m[2] === '');
    if ($m[2] !== '') $end = min((int)$m[2], $size - 1);
    if ($start > $end || $start >= $size) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header("Content-Range: bytes */$size");
        exit;
    }
}

// رنج «باز» (bytes=N-) را در قطعات حداکثر ۶ مگابایتی جواب می‌دهیم (۲۰۶ استاندارد؛ مرورگر خودش قطعه‌ی بعدی را می‌خواهد).
// دلیل: قبلاً هر پخش، یک پردازش PHP را تا آخر فایل (دقیقه‌ها، مخصوصاً روی اینترنت کند موبایل) اشغال می‌کرد؛
// با چند ده بیننده‌ی هم‌زمان، سقف پردازش‌های هاست پر می‌شد و کل سایت (صفحه‌ها و ورود) چند دقیقه باز نمی‌شد.
define('MM_RANGE_CHUNK', 6 * 1024 * 1024);
if ($isRange && $openEnded && ($end - $start + 1) > MM_RANGE_CHUNK) $end = $start + MM_RANGE_CHUNK - 1;

$length = $end - $start + 1;

// اگه همین رنج قبلاً توی کش مرورگره، نیازی به ارسال دوباره نیست
$ifRange = isset($_SERVER['HTTP_IF_RANGE']) ? $_SERVER['HTTP_IF_RANGE'] : '';
if ($ifRange && $ifRange !== $etag) { $isRange = false; $start = 0; $end = $size - 1; $length = $size; }

header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('Content-Length: ' . $length);
header('Cache-Control: private, max-age=86400, immutable');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');
header('X-Accel-Buffering: no'); // برای nginx پشت لایه‌سرور، اگه بود
header('Connection: keep-alive');
header('Content-Disposition: inline');

if ($isRange) {
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
}

@set_time_limit(0);
ignore_user_abort(false);

$fp = fopen($path, 'rb');
if (!$fp) { http_response_code(500); exit; }

if ($start > 0) fseek($fp, $start);

if ($isRange && $end < $size - 1) {
    // فقط بخشی از فایل — با stream_copy_to_stream سریع و بدون لوپ دستی ارسال می‌شه
    stream_copy_to_stream($fp, fopen('php://output', 'wb'), $length);
} else {
    // تا انتهای فایل — fpassthru سریع‌ترین روشه (سطح C، بدون لوپ PHP)
    fpassthru($fp);
}
fclose($fp);
