<?php
/**
 * mm-loadtest — شبیه‌سازی چند بیننده‌ی هم‌زمان روی stream.php
 *
 * فقط می‌خواند؛ چیزی نمی‌نویسد و هیچ فایلی را تغییر نمی‌دهد. به دیتابیس هم وصل نمی‌شود.
 * بعد از گرفتن نتیجه، فایل را از هاست حذف کن.
 *
 * بدون پارامتر n:  فقط وضعیت سرور + نرخ‌بیت و ساختار فایل‌های ویدیو (بدون هیچ فشاری)
 * با n:            n بیننده‌ی هم‌زمان، هرکدام s ثانیه
 *
 *   ?k=KEY                         → وضعیت و پروفایل ویدیوها
 *   ?k=KEY&n=1&s=8                 → یک بیننده (مبنا)
 *   ?k=KEY&n=10&s=10               → ۱۰ بیننده‌ی هم‌زمان، همه روی یک جلسه
 *   ?k=KEY&n=20&s=10               → ۲۰ بیننده
 *   ?k=KEY&n=20&s=10&mode=different → ۲۰ بیننده روی جلسه‌های مختلف (بدون کمک کش دیسک)
 *
 * نکته: درخواست‌ها از خود سرور به آدرس عمومی می‌روند؛ اگر هاست برای «هر IP» سقف جدا داشته باشد،
 * نتیجه کمی بدبینانه‌تر از دنیای واقعی (هر کاربر با IP خودش) می‌شود.
 */
$KEY = 'fb0e36efc0802e25';
if (!isset($_GET['k']) || !hash_equals($KEY, (string)$_GET['k'])) { http_response_code(404); exit; }

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
@set_time_limit(120);
@ini_set('display_errors', '0');
ignore_user_abort(true);

// برای تست محلی عوض می‌شود؛ روی هاست واقعی همین مقادیر درست‌اند
$SCHEME = 'https';
$HOSTS  = ['upload.mehdimehrabi.ir', 'mehdimehrabi.ir'];

require_once __DIR__ . '/api/config.php';   // فقط برای SERVICE_KEY (امضای توکن تست)؛ چاپ نمی‌شود

function line($k, $v = '') { echo str_pad($k, 36) . ': ' . $v . "\n"; }
function head($t) { echo "\n== $t ==\n"; }

/** مدت (ثانیه) و اینکه moov قبل از mdat است (faststart) را از ساختار MP4 می‌خواند */
function mm_mp4_info($path) {
    $fp = @fopen($path, 'rb');
    if (!$fp) return null;
    $size = (int)@filesize($path);
    $pos = 0; $guard = 0; $moov = null; $mdat = null; $dur = null;
    while ($pos + 8 <= $size && $guard++ < 200) {
        fseek($fp, $pos);
        $h = fread($fp, 16);
        if (strlen($h) < 8) break;
        $len = unpack('N', substr($h, 0, 4))[1];
        $type = substr($h, 4, 4);
        $hdr = 8;
        if ($len == 1 && strlen($h) >= 16) {
            $len = unpack('N', substr($h, 8, 4))[1] * 4294967296 + unpack('N', substr($h, 12, 4))[1];
            $hdr = 16;
        } elseif ($len == 0) {
            $len = $size - $pos;
        }
        if ($len < $hdr) break;
        if ($type === 'moov' && $moov === null) {
            $moov = $pos;
            fseek($fp, $pos + $hdr);
            $buf = fread($fp, 512);
            $i = strpos($buf, 'mvhd');
            if ($i !== false) {
                $o = $i + 4;
                $ver = ord($buf[$o]);
                if ($ver === 1 && strlen($buf) >= $o + 32) {
                    $ts = unpack('N', substr($buf, $o + 20, 4))[1];
                    $d  = unpack('N', substr($buf, $o + 24, 4))[1] * 4294967296 + unpack('N', substr($buf, $o + 28, 4))[1];
                    if ($ts > 0) $dur = $d / $ts;
                } elseif ($ver === 0 && strlen($buf) >= $o + 20) {
                    $ts = unpack('N', substr($buf, $o + 12, 4))[1];
                    $d  = unpack('N', substr($buf, $o + 16, 4))[1];
                    if ($ts > 0) $dur = $d / $ts;
                }
            }
        }
        if ($type === 'mdat' && $mdat === null) $mdat = $pos;
        if ($moov !== null && $mdat !== null) break;
        $pos += $len;
    }
    fclose($fp);
    return ['size' => $size, 'duration' => $dur, 'faststart' => ($moov !== null && ($mdat === null || $moov < $mdat))];
}

function mm_token($file) {
    $payload = ['f' => $file, 'u' => 'loadtest', 'exp' => time() + 600];
    $b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    return $b64 . '.' . hash_hmac('sha256', $b64, SERVICE_KEY);
}

function median($a) { sort($a); $n = count($a); if (!$n) return 0; return $n % 2 ? $a[intval($n / 2)] : ($a[$n / 2 - 1] + $a[$n / 2]) / 2; }

echo "mm-loadtest " . gmdate('c') . "\n";

// ── وضعیت سرور ──
head('server now');
$cores = 0;
$ci = @file_get_contents('/proc/cpuinfo');
if ($ci) $cores = preg_match_all('/^processor\s*:/m', $ci);
line('cpu cores visible', $cores ?: '-');
$la = function_exists('sys_getloadavg') ? @sys_getloadavg() : null;
line('load average (1/5/15 min)', is_array($la) ? implode(' ', array_map(function ($x) { return number_format($x, 2); }, $la)) : '-');
$mi = @file_get_contents('/proc/meminfo');
if ($mi && preg_match('/MemAvailable:\s+(\d+)/', $mi, $m)) line('memory available (GB)', number_format($m[1] / 1048576, 1));
if ($mi && preg_match('/MemTotal:\s+(\d+)/', $mi, $m)) line('memory total (GB)', number_format($m[1] / 1048576, 1));

// ── پروفایل ویدیوها ──
head('video profile (no names)');
$vdir = dirname(__DIR__) . '/protected_videos';
$files = [];
foreach ((array)@scandir($vdir) as $f) {
    if ($f === '.' || $f === '..') continue;
    if (!preg_match('/\.(mp4|m4v)$/i', $f)) continue;
    if (is_file($vdir . '/' . $f)) $files[] = $f;
}
sort($files);
$info = [];
foreach ($files as $i => $f) {
    $in = mm_mp4_info($vdir . '/' . $f);
    if (!$in) continue;
    $mbps = ($in['duration'] > 0) ? ($in['size'] * 8 / 1e6) / $in['duration'] : null;
    $info[$f] = $in + ['mbps' => $mbps];
    echo sprintf("#%02d  %7.1f MB  %6.1f min  %5.2f Mbps  faststart=%s\n", $i + 1, $in['size'] / 1048576, $in['duration'] ? $in['duration'] / 60 : 0, $mbps ?: 0, $in['faststart'] ? 'yes' : 'NO');
}
$rates = array_values(array_filter(array_map(function ($x) { return $x['mbps']; }, $info)));
if ($rates) {
    line('mp4 files', count($info));
    line('bitrate Mbps (min/median/max)', number_format(min($rates), 2) . ' / ' . number_format(median($rates), 2) . ' / ' . number_format(max($rates), 2));
    $nofast = 0; foreach ($info as $x) if (!$x['faststart']) $nofast++;
    line('files WITHOUT faststart', $nofast);
    line('demand for 20 viewers @median (MB/s)', number_format(20 * median($rates) / 8, 2));
    line('demand for 20 viewers @max (MB/s)', number_format(20 * max($rates) / 8, 2));
}

// ── تست بار ──
if (!isset($_GET['n'])) { echo "\n(برای تست بار پارامتر n را بده؛ مثال: &n=10&s=10)\n"; exit; }

$n = max(1, min(30, (int)$_GET['n']));
$secs = max(3, min(15, isset($_GET['s']) ? (int)$_GET['s'] : 10));
$mode = (isset($_GET['mode']) && $_GET['mode'] === 'different') ? 'different' : 'same';
$host = (isset($_GET['host']) && in_array($_GET['host'], $HOSTS, true)) ? $_GET['host'] : $HOSTS[0];
if (!$files) { echo "\nno mp4 files found\n"; exit; }

// فایل «یکسان»: میانه‌ی فایل‌ها از نظر حجم (نه کوچک‌ترین، نه بزرگ‌ترین)
$bySize = $files;
usort($bySize, function ($a, $b) use ($info) { return $info[$a]['size'] <=> $info[$b]['size']; });
$sameFile = $bySize[intval(count($bySize) / 2)];

head("load test: n=$n  seconds=$secs  mode=$mode  host=$host");
$mh = curl_multi_init();
$hs = []; $st = [];
for ($i = 0; $i < $n; $i++) {
    $f = ($mode === 'same') ? $sameFile : $files[$i % count($files)];
    $st[$i] = ['bytes' => 0, 'first' => null, 'last' => null];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$SCHEME://$host/stream.php?t=" . mm_token($f),
        CURLOPT_HTTPHEADER => ['Range: bytes=0-', 'Sec-Fetch-Dest: video', 'Sec-Fetch-Mode: no-cors', 'Sec-Fetch-Site: same-site',
            'Referer: https://mehdimehrabi.ir/watch.html', 'Accept-Encoding: identity', 'User-Agent: Mozilla/5.0 (mm-loadtest)'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $secs + 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_WRITEFUNCTION => function ($c, $data) use (&$st, $i, $secs) {
            $now = microtime(true);
            if ($st[$i]['first'] === null) $st[$i]['first'] = $now;
            $st[$i]['bytes'] += strlen($data);
            $st[$i]['last'] = $now;
            return ($now - $st[$i]['first'] >= $secs) ? 0 : strlen($data);   // بعد از s ثانیه قطع می‌کنیم
        },
    ]);
    curl_multi_add_handle($mh, $ch);
    $hs[$i] = $ch;
}
$la0 = function_exists('sys_getloadavg') ? @sys_getloadavg() : null;
$t0 = microtime(true);
do {
    curl_multi_exec($mh, $running);
    if ($running) curl_multi_select($mh, 0.1);
} while ($running && (microtime(true) - $t0) < $secs + 20);
$wall = microtime(true) - $t0;
$la1 = function_exists('sys_getloadavg') ? @sys_getloadavg() : null;

$okSpeeds = []; $ttfbs = []; $codes = []; $totalBytes = 0; $minFirst = null; $maxLast = null;
foreach ($hs as $i => $ch) {
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ttfb = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME);
    $codes[$code] = isset($codes[$code]) ? $codes[$code] + 1 : 1;
    $s = $st[$i];
    if (($code === 200 || $code === 206) && $s['first'] !== null) {
        $dt = max(0.05, $s['last'] - $s['first']);
        $okSpeeds[] = ($s['bytes'] * 8 / 1e6) / $dt;
        $ttfbs[] = $ttfb * 1000;
        $totalBytes += $s['bytes'];
        if ($minFirst === null || $s['first'] < $minFirst) $minFirst = $s['first'];
        if ($maxLast === null || $s['last'] > $maxLast) $maxLast = $s['last'];
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

ksort($codes);
$codeStr = []; foreach ($codes as $c => $k) $codeStr[] = ($c ?: 'no-response') . ' x' . $k;
line('HTTP results', implode(', ', $codeStr));
line('streams OK / requested', count($okSpeeds) . ' / ' . $n);
line('wall time (s)', number_format($wall, 1));
if ($okSpeeds) {
    $span = max(0.1, $maxLast - $minFirst);
    line('total throughput (MB/s)', number_format($totalBytes / 1048576 / $span, 1));
    line('per-stream Mbps min/median/max', number_format(min($okSpeeds), 1) . ' / ' . number_format(median($okSpeeds), 1) . ' / ' . number_format(max($okSpeeds), 1));
    line('time to first byte ms min/median/max', number_format(min($ttfbs), 0) . ' / ' . number_format(median($ttfbs), 0) . ' / ' . number_format(max($ttfbs), 0));
}
line('load avg before', is_array($la0) ? implode(' ', array_map(function ($x) { return number_format($x, 2); }, $la0)) : '-');
line('load avg after', is_array($la1) ? implode(' ', array_map(function ($x) { return number_format($x, 2); }, $la1)) : '-');
echo "\nدر پایان: این فایل را از هاست حذف کن.\n";
