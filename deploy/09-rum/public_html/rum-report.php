<?php
/** گزارش کیفیت اتصال بازدیدکننده‌ها — فقط با کلید. نمونه: rum-report.php?k=KEY&d=2 (d = چند روز اخیر، حداکثر ۷) */
$KEY = 'b59042a8bdd572d10771';
if (!isset($_GET['k']) || !hash_equals($KEY, (string)$_GET['k'])) { http_response_code(404); exit; }
@ini_set('display_errors', '0'); @set_time_limit(60); @ini_set('memory_limit', '256M');
date_default_timezone_set('Asia/Tehran');
header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');

$dirs = [dirname(__DIR__) . '/mm-logs', __DIR__ . '/.mm-logs'];
$dir = null; foreach ($dirs as $x) if (is_dir($x)) { $dir = $x; break; }
$days = max(1, min(7, (int)($_GET['d'] ?? 2)));
$since = time() - $days * 86400;

function pct($a, $p) { if (!$a) return '—'; sort($a); return (string)$a[min(count($a) - 1, (int)floor(count($a) * $p))]; }
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function net24($ip) {
    if (strpos($ip, ':') !== false) { $p = explode(':', $ip); return implode(':', array_slice($p, 0, 3)) . '::/48'; }
    $p = explode('.', $ip); return count($p) === 4 ? "$p[0].$p[1].$p[2].0/24" : $ip;
}
function browser($ua) {
    $os = stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false ? 'iOS' : (stripos($ua, 'Android') !== false ? 'Android' : (stripos($ua, 'Windows') !== false ? 'Windows' : (stripos($ua, 'Mac') !== false ? 'Mac' : '?')));
    $b = preg_match('/(CriOS|Chrome|Firefox|FxiOS|Edg|OPR|SamsungBrowser)\//', $ua, $m) ? $m[1] : (stripos($ua, 'Safari/') !== false ? 'Safari' : '?');
    return "$os/$b";
}

$loads = []; $evs = []; $n = 0; $ips = []; $cids = [];
if ($dir) foreach (glob($dir . '/rum-*.jsonl') ?: [] as $f) {
    if (filemtime($f) < $since) continue;
    $fh = fopen($f, 'r'); if (!$fh) continue;
    while (($ln = fgets($fh)) !== false) {
        $r = json_decode($ln, true); if (!is_array($r) || ($r['t'] ?? 0) < $since) continue;
        $d = $r['d'] ?? []; $n++; $ips[$r['ip']] = 1; if (!empty($d['cid'])) $cids[$d['cid']] = 1;
        $k = $d['k'] ?? '';
        if ($k === 'load') $loads[] = $r; else $evs[] = $r;
    }
    fclose($fh);
}
$col = function ($key) use ($loads) { $o = []; foreach ($loads as $r) { $v = $r['d']['nav'][$key] ?? null; if ($v !== null && $v !== '') $o[] = (int)$v; } return $o; };

echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>گزارش کیفیت اتصال</title>
<style>body{font:14px/1.7 Tahoma,sans-serif;margin:16px;background:#fafafa;color:#222}h2{margin:24px 0 8px}table{border-collapse:collapse;background:#fff;margin:6px 0;font-size:13px}td,th{border:1px solid #ddd;padding:4px 10px;text-align:right}th{background:#f0f0f0}.bad{color:#b00020;font-weight:700}.m{font-family:monospace;direction:ltr;unicode-bidi:embed}</style>';
echo '<h1>گزارش کیفیت اتصال — ' . $days . ' روز اخیر</h1>';
if (!$dir) { echo '<p class="bad">هنوز هیچ لاگی ثبت نشده است (پوشه‌ی mm-logs وجود ندارد).</p>'; exit; }
echo '<p>' . count($loads) . ' بازدید ثبت‌شده · ' . count($ips) . ' آی‌پی · ' . count($cids) . ' مرورگر یکتا · ' . count($evs) . ' رویداد خطا/پخش · ' . date('Y-m-d H:i') . ' (تهران)</p>';
echo '<p>زمان‌ها به میلی‌ثانیه‌اند. «ttfb» = تا رسیدن اولین بایت صفحه؛ «dns» و «con» = پیدا کردن آدرس و برقراری اتصال.</p>';

echo '<h2>۱) خلاصه‌ی زمان‌ها</h2><table><tr><th>معیار</th><th>میانه</th><th>۹۰٪</th><th>۹۹٪</th></tr>';
foreach (['dns' => 'DNS', 'con' => 'اتصال (TCP)', 'tls' => 'TLS', 'ttfb' => 'اولین بایت (ttfb)', 'srv' => 'پاسخ سرور', 'load' => 'بارگذاری کامل'] as $k => $lab) {
    $a = $col($k); echo '<tr><td>' . $lab . '</td><td>' . pct($a, .5) . '</td><td>' . pct($a, .9) . '</td><td>' . pct($a, .99) . '</td></tr>';
}
echo '</table>';

$pr = []; foreach ($loads as $r) { $p = $r['d']['nav']['proto'] ?? '?'; $pr[$p] = ($pr[$p] ?? 0) + 1; } arsort($pr);
echo '<h2>۲) پروتکل (h2/h3 خوب است؛ http/1.1 یعنی HTTP/2 روی هاست فعال نیست)</h2><table><tr><th>پروتکل</th><th>تعداد</th></tr>';
foreach ($pr as $p => $c) echo '<tr><td class="m">' . e($p) . '</td><td>' . $c . '</td></tr>'; echo '</table>';

$ct = []; foreach ($loads as $r) { $t = $r['d']['con']['et'] ?? '?'; $ct[$t][] = (int)($r['d']['nav']['ttfb'] ?? 0); } 
echo '<h2>۳) نوع اتصال کاربر (بر اساس مرورگر)</h2><table><tr><th>نوع</th><th>تعداد</th><th>میانه ttfb</th><th>۹۰٪ ttfb</th></tr>';
foreach ($ct as $t => $a) echo '<tr><td class="m">' . e($t) . '</td><td>' . count($a) . '</td><td>' . pct($a, .5) . '</td><td>' . pct($a, .9) . '</td></tr>'; echo '</table>';

$hr = []; foreach ($loads as $r) { $h = date('m-d H', $r['t']); $hr[$h]['n'] = ($hr[$h]['n'] ?? 0) + 1; if (($r['d']['nav']['load'] ?? 0) > 8000 || ($r['d']['nav']['ttfb'] ?? 0) > 3000) $hr[$h]['s'] = ($hr[$h]['s'] ?? 0) + 1; }
ksort($hr); echo '<h2>۴) بازدید و بازدیدِ کند در هر ساعت (کند = بارگذاری بیش از ۸ ثانیه یا ttfb بیش از ۳ ثانیه)</h2><table><tr><th>ساعت</th><th>بازدید</th><th>کند</th></tr>';
foreach ($hr as $h => $v) { $s = $v['s'] ?? 0; echo '<tr><td class="m">' . $h . '</td><td>' . $v['n'] . '</td><td' . ($s ? ' class="bad"' : '') . '>' . $s . '</td></tr>'; } echo '</table>';

$ng = []; foreach ($loads as $r) { $k = net24($r['ip']); $ng[$k]['n'] = ($ng[$k]['n'] ?? 0) + 1; $ng[$k]['t'][] = (int)($r['d']['nav']['ttfb'] ?? 0); $ng[$k]['d'][] = (int)($r['d']['nav']['dns'] ?? 0); $ng[$k]['c'][] = (int)($r['d']['nav']['con'] ?? 0); }
uasort($ng, function ($a, $b) { return $b['n'] <=> $a['n']; });
echo '<h2>۵) به تفکیک شبکه‌ی کاربر (۱۵ شبکه‌ی پرتکرار؛ هر کدام معمولاً یک اپراتور/مودم)</h2><table><tr><th>شبکه</th><th>بازدید</th><th>میانه dns</th><th>میانه اتصال</th><th>۹۰٪ ttfb</th></tr>';
foreach (array_slice($ng, 0, 15, true) as $k => $v) echo '<tr><td class="m">' . e($k) . '</td><td>' . $v['n'] . '</td><td>' . pct($v['d'], .5) . '</td><td>' . pct($v['c'], .5) . '</td><td>' . pct($v['t'], .9) . '</td></tr>'; echo '</table>';

echo '<h2>۶) کندترین بازدیدها (۲۵ مورد)</h2><table><tr><th>زمان</th><th>صفحه</th><th>آی‌پی</th><th>مرورگر</th><th>اتصال</th><th>dns</th><th>con</th><th>tls</th><th>ttfb</th><th>load</th></tr>';
usort($loads, function ($a, $b) { return ($b['d']['nav']['load'] ?? 0) <=> ($a['d']['nav']['load'] ?? 0); });
foreach (array_slice($loads, 0, 25) as $r) { $v = $r['d']['nav'] ?? [];
    echo '<tr><td class="m">' . date('m-d H:i:s', $r['t']) . '</td><td class="m">' . e($r['d']['p'] ?? '') . '</td><td class="m">' . e($r['ip']) . '</td><td>' . e(browser($r['ua'])) . '</td><td>' . e($r['d']['con']['et'] ?? '?') . '</td><td>' . ($v['dns'] ?? '') . '</td><td>' . ($v['con'] ?? '') . '</td><td>' . ($v['tls'] ?? '') . '</td><td>' . ($v['ttfb'] ?? '') . '</td><td>' . ($v['load'] ?? '') . '</td></tr>'; }
echo '</table>';

echo '<h2>۷) خطاها و مشکل پخش (جدیدترین ۶۰ مورد)</h2><table><tr><th>زمان</th><th>نوع</th><th>صفحه</th><th>آی‌پی</th><th>مرورگر</th><th>اتصال</th><th>جزئیات</th></tr>';
usort($evs, function ($a, $b) { return $b['t'] <=> $a['t']; });
$loadsWithErr = array_filter($loads, function ($r) { $d = $r['d']; return !empty($d['res']) || !empty($d['fx']) || !empty($d['err']); });
$rows = array_merge($evs, $loadsWithErr); usort($rows, function ($a, $b) { return $b['t'] <=> $a['t']; });
$shown = 0;
foreach ($rows as $r) { $d = $r['d'];
    $det = []; foreach (['res' => 'منبع خراب', 'fx' => 'درخواست ناموفق/کند', 'err' => 'خطای JS', 'vid' => 'ویدیو'] as $k => $lab) if (!empty($d[$k])) $det[] = $lab . ': ' . json_encode($d[$k], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!$det) continue;
    if (++$shown > 60) break;
    echo '<tr><td class="m">' . date('m-d H:i:s', $r['t']) . '</td><td>' . e($d['k'] ?? '') . '</td><td class="m">' . e($d['p'] ?? '') . '</td><td class="m">' . e($r['ip']) . '</td><td>' . e(browser($r['ua'])) . '</td><td>' . e($d['con']['et'] ?? '?') . '</td><td class="m" style="max-width:520px;word-break:break-all">' . e(implode(' | ', $det)) . '</td></tr>'; }
echo '</table>';
