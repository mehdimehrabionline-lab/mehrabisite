<?php
/**
 * تشخیص محیط هاست — فقط‌خواندنی، هیچ چیزی تغییر نمی‌دهد (جز ساخت/حذف یک پوشه‌ی تستی کوچک)
 * بعد از گرفتن نتیجه، این فایل را از هاست حذف کن.
 *
 * آدرس: https://mehdimehrabi.ir/mm-diag-ad2f4f36c398b026.php?k=ad2f4f36c398b026
 */
$KEY = 'ad2f4f36c398b026';
if (!isset($_GET['k']) || !hash_equals($KEY, (string)$_GET['k'])) { http_response_code(404); exit; }

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
@set_time_limit(90);
@ini_set('display_errors', '0');

function line($k, $v = '') { echo str_pad($k, 34) . ': ' . $v . "\n"; }
function head($t) { echo "\n== $t ==\n"; }
function ms($s) { return number_format($s * 1000, 0) . 'ms'; }
function yn($b) { return $b ? 'yes' : 'no'; }

echo "mm-diag " . gmdate('c') . "\n";

// ── سرور و PHP ──
head('server / php');
line('PHP version', PHP_VERSION);
line('SAPI', PHP_SAPI);
line('SERVER_SOFTWARE', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '-');
line('SERVER_PROTOCOL', isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '-');
line('LSWS_EDITION', isset($_SERVER['LSWS_EDITION']) ? $_SERVER['LSWS_EDITION'] : '-');
line('memory_limit', ini_get('memory_limit'));
line('max_execution_time', ini_get('max_execution_time'));
line('upload_max_filesize', ini_get('upload_max_filesize'));
line('post_max_size', ini_get('post_max_size'));
line('output_buffering', ini_get('output_buffering'));
line('zlib.output_compression', ini_get('zlib.output_compression'));
line('opcache.enable', ini_get('opcache.enable'));
line('disable_functions', ini_get('disable_functions') ?: '(none)');

head('functions / extensions');
foreach (['curl_init', 'curl_multi_init', 'litespeed_finish_request', 'fastcgi_finish_request', 'apache_get_modules', 'symlink', 'apcu_fetch', 'sys_getloadavg', 'random_bytes', 'hash_hmac', 'mb_substr'] as $f) {
    line($f, yn(function_exists($f)));
}
if (function_exists('apache_get_modules')) {
    $mods = @apache_get_modules();
    if (is_array($mods)) {
        $want = ['mod_xsendfile', 'mod_deflate', 'mod_http2', 'mod_headers', 'mod_expires', 'mod_rewrite', 'mod_cloudflare', 'mod_remoteip'];
        foreach ($want as $m) line($m, yn(in_array($m, $mods)));
    }
}
if (function_exists('curl_version')) {
    $cv = curl_version();
    line('curl version', $cv['version']);
    line('curl http2', yn(defined('CURL_VERSION_HTTP2') && ($cv['features'] & CURL_VERSION_HTTP2)));
    line('curl libz', yn(defined('CURL_VERSION_LIBZ') && ($cv['features'] & CURL_VERSION_LIBZ)));
}

// ── پوشه‌ها و فضا ──
head('folders / disk');
$home = dirname(__DIR__);
$vdir = $home . '/protected_videos';
line('home writable', yn(is_writable($home)));
line('protected_videos exists', yn(is_dir($vdir)));
if (is_dir($vdir)) {
    $n = 0; $total = 0; $largest = 0; $pick = '';
    foreach ((array)@scandir($vdir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $vdir . '/' . $f;
        if (!is_file($p)) continue;
        $n++;
        $sz = (int)@filesize($p);
        $total += $sz;
        if ($sz > $largest) { $largest = $sz; $pick = $p; }
    }
    line('files in protected_videos', $n);
    line('total size (MB)', number_format($total / 1048576, 1));
    line('largest file (MB)', number_format($largest / 1048576, 1));
}
$free = @disk_free_space($home);
line('disk free (GB)', $free === false ? '-' : number_format($free / 1073741824, 2));
line('api/cache writable', yn(is_writable(__DIR__ . '/api/cache')));
// تست ساخت پوشه‌ی کش خصوصی
$td = $home . '/mm_cache_probe_' . bin2hex(random_bytes(3));
$mk = @mkdir($td, 0750);
$wr = $mk ? (@file_put_contents($td . '/t', 'x') !== false) : false;
line('can create private cache dir', yn($mk && $wr));
if ($mk) { @unlink($td . '/t'); @rmdir($td); }
if (function_exists('sys_getloadavg')) {
    $la = @sys_getloadavg();
    if (is_array($la)) line('load average', implode(' ', array_map(function ($x) { return number_format($x, 2); }, $la)));
}

// ── سرعت خواندن دیسک (۳۲ مگابایت اول بزرگ‌ترین ویدیو؛ فقط می‌خواند، چیزی نمی‌فرستد) ──
head('disk read speed');
if (!empty($pick)) {
    $t0 = microtime(true);
    $fp = @fopen($pick, 'rb');
    $read = 0;
    if ($fp) {
        while ($read < 33554432 && !feof($fp)) {
            $b = fread($fp, 1048576);
            if ($b === false || $b === '') break;
            $read += strlen($b);
        }
        fclose($fp);
    }
    $dt = max(0.0001, microtime(true) - $t0);
    line('read MB', number_format($read / 1048576, 1));
    line('MB/s (cold/warm mixed)', number_format(($read / 1048576) / $dt, 0));
} else {
    line('no video file to test', '-');
}

// ── تأخیر هاست تا Supabase (همان مسیری که mycourses.php می‌رود) ──
head('host -> supabase latency');
$anon = '';
$cfg = $home . '/public_html/supabase-config.js';
if (!is_file($cfg)) $cfg = __DIR__ . '/supabase-config.js';
if (is_file($cfg) && preg_match("/SUPABASE_ANON_KEY\s*=\s*['\"]([^'\"]+)['\"]/", (string)@file_get_contents($cfg), $m)) $anon = $m[1];
$url = 'https://rknsiuyfhxnmhnfesfhh.supabase.co/rest/v1/courses?select=id&limit=1';
$hdr = $anon ? ['apikey: ' . $anon, 'Authorization: Bearer ' . $anon] : [];

function probe($ch, $url, $hdr) {
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_HTTPHEADER => $hdr, CURLOPT_SSL_VERIFYPEER => false]);
    $t0 = microtime(true);
    curl_exec($ch);
    $i = curl_getinfo($ch);
    $err = curl_errno($ch);
    return ['err' => $err, 'code' => $i['http_code'], 'dns' => $i['namelookup_time'], 'conn' => $i['connect_time'], 'tls' => $i['appconnect_time'], 'ttfb' => $i['starttransfer_time'], 'total' => microtime(true) - $t0];
}
if (function_exists('curl_init')) {
    echo "-- ۳ درخواست پشت‌سرهم، هرکدام اتصال جدید (روش فعلی sb_request) --\n";
    $sumNew = 0;
    for ($k = 1; $k <= 3; $k++) {
        $ch = curl_init();
        $r = probe($ch, $url, $hdr);
        curl_close($ch);
        $sumNew += $r['total'];
        line("new #$k", "http={$r['code']} err={$r['err']} dns=" . ms($r['dns']) . " connect=" . ms($r['conn']) . " tls=" . ms($r['tls']) . " ttfb=" . ms($r['ttfb']) . " total=" . ms($r['total']));
    }
    line('sum (3 new connections)', ms($sumNew));

    echo "-- ۳ درخواست پشت‌سرهم روی یک اتصال (keep-alive) --\n";
    $sumKeep = 0;
    $ch = curl_init();
    for ($k = 1; $k <= 3; $k++) {
        $r = probe($ch, $url, $hdr);
        $sumKeep += $r['total'];
        line("reused #$k", "http={$r['code']} err={$r['err']} connect=" . ms($r['conn']) . " ttfb=" . ms($r['ttfb']) . " total=" . ms($r['total']));
    }
    curl_close($ch);
    line('sum (1 connection, 3 requests)', ms($sumKeep));

    if (function_exists('curl_multi_init')) {
        echo "-- ۲ درخواست هم‌زمان (curl_multi) --\n";
        $mh = curl_multi_init();
        $hs = [];
        for ($k = 0; $k < 2; $k++) {
            $c = curl_init($url);
            curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_HTTPHEADER => $hdr, CURLOPT_SSL_VERIFYPEER => false]);
            curl_multi_add_handle($mh, $c);
            $hs[] = $c;
        }
        $t0 = microtime(true);
        $run = null;
        do { curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 0.2); } while ($run);
        line('wall time (2 parallel)', ms(microtime(true) - $t0));
        foreach ($hs as $c) { curl_multi_remove_handle($mh, $c); curl_close($c); }
        curl_multi_close($mh);
    }
} else {
    line('curl', 'not available');
}

echo "\nدر پایان: این فایل را از هاست حذف کن.\n";
