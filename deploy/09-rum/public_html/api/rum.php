<?php
/**
 * ثبت کیفیت اتصال بازدیدکننده‌ها (RUM) — فقط می‌نویسد؛ هرگز چیزی به کاربر نشان نمی‌دهد و هرگز خطا برنمی‌گرداند.
 * داده: آی‌پی، مرورگر، زمان‌های DNS/اتصال/پاسخ، خطاهای بارگذاری و پخش. (بدون موبایل/نام/توکن)
 * لاگ‌ها ۱۴ روز نگه‌داشته می‌شوند. گزارش: rum-report.php?k=...
 */
@ini_set('display_errors', '0');
header('Cache-Control: no-store');

function mm_rum_dir() {
    $cands = [dirname(__DIR__, 2) . '/mm-logs', dirname(__DIR__) . '/.mm-logs'];
    foreach ($cands as $d) {
        if (!is_dir($d)) @mkdir($d, 0700, true);
        if (is_dir($d) && is_writable($d)) {
            if (strpos($d, '/.mm-logs') !== false && !is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\nDeny from all\n");
            return $d;
        }
    }
    return null;
}

function mm_rum_clean($v, $depth = 0) {
    if (is_array($v)) {
        if ($depth > 3) return null;
        $out = []; $n = 0;
        foreach ($v as $k => $x) {
            if (++$n > 10) break;
            if (is_string($k) && !preg_match('/^[a-z0-9_]{1,12}$/i', $k)) continue;
            $out[$k] = mm_rum_clean($x, $depth + 1);
        }
        return $out;
    }
    if (is_int($v) || is_float($v)) return is_finite((float)$v) ? round((float)$v) : 0;
    if (is_bool($v)) return $v ? 1 : 0;
    if (is_string($v)) return mb_substr(preg_replace('/[\x00-\x1f]+/', ' ', $v), 0, 120);
    return null;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(204); exit; }
    $raw = file_get_contents('php://input', false, null, 0, 6145);
    if ($raw === false || strlen($raw) < 2 || strlen($raw) > 6144) { http_response_code(204); exit; }
    $d = json_decode($raw, true);
    if (!is_array($d)) { http_response_code(204); exit; }
    $dir = mm_rum_dir();
    if (!$dir) { http_response_code(204); exit; }

    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    $now = time();

    // سقف ۱۲۰ گزارش در ۱۰ دقیقه برای هر آی‌پی (جلوگیری از پر شدن لاگ)
    @mkdir($dir . '/rl', 0700, true);
    $rf = $dir . '/rl/' . substr(md5($ip), 0, 16);
    $c = 0; $w = $now;
    if (is_file($rf)) { $p = explode(' ', (string)@file_get_contents($rf)); if (count($p) === 2) { $c = (int)$p[0]; $w = (int)$p[1]; } }
    if ($now - $w > 600) { $c = 0; $w = $now; }
    if ($c >= 120) { http_response_code(204); exit; }
    @file_put_contents($rf, ($c + 1) . ' ' . $w, LOCK_EX);

    $line = json_encode([
        't'  => $now,
        'ip' => $ip,
        'ua' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 160),
        'd'  => mm_rum_clean($d),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $f = $dir . '/rum-' . date('Ymd', $now) . '.jsonl';
    if (!is_file($f) || filesize($f) < 8 * 1024 * 1024) @file_put_contents($f, $line . "\n", FILE_APPEND | LOCK_EX);

    // پاک‌سازی گاه‌به‌گاه: لاگ‌های بیش از ۱۴ روز و فایل‌های قدیمی سقف
    if (mt_rand(1, 100) === 1) {
        foreach (glob($dir . '/rum-*.jsonl') ?: [] as $old) if (filemtime($old) < $now - 14 * 86400) @unlink($old);
        foreach (glob($dir . '/rl/*') ?: [] as $old) if (filemtime($old) < $now - 3600) @unlink($old);
    }
} catch (Throwable $e) { /* بی‌صدا */ }
http_response_code(204);
