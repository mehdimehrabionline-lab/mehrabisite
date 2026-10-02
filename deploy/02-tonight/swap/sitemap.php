<?php
/**
 * نقشه سایت پویا — دوره‌ها و مقالات را خودکار اضافه می‌کند
 */
header('Content-Type: application/xml; charset=utf-8');

$SUPABASE_URL = 'https://rknsiuyfhxnmhnfesfhh.supabase.co';
$ANON_KEY = '';
$cfg = __DIR__ . '/supabase-config.js';
if (file_exists($cfg) && preg_match("/SUPABASE_ANON_KEY\s*=\s*['\"]([^'\"]+)['\"]/", file_get_contents($cfg), $m)) {
    $ANON_KEY = $m[1];
}

function fetchRows($path, $key, $url) {
    if (!$key) return [];
    $ch = curl_init($url . '/rest/v1/' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['apikey: ' . $key, 'Authorization: Bearer ' . $key],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    // فقط «لیست» قبول می‌شه؛ اگه Supabase پیام خطا (یک آبجکت) برگردونه، لیست خالی برمی‌گردونیم تا نقشه‌ی سایت خراب نشه
    if (!is_array($d) || (!empty($d) && !isset($d[0]))) return [];
    return $d;
}

$base = 'https://mehdimehrabi.ir';
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// صفحات ثابت
$static = [
    ['/', '1.0', 'daily'],
    ['/blog.html', '0.9', 'daily'],
];
foreach ($static as $s) {
    echo "  <url>\n";
    echo "    <loc>{$base}{$s[0]}</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$s[2]}</changefreq>\n";
    echo "    <priority>{$s[1]}</priority>\n";
    echo "  </url>\n";
}

// دوره‌ها
// (جدول courses ستون updated_at ندارد؛ از created_at استفاده می‌کنیم)
$courses = fetchRows('courses?select=id,slug,created_at&is_active=eq.true', $ANON_KEY, $SUPABASE_URL);
foreach ($courses as $c) {
    if (!is_array($c) || (empty($c['slug']) && empty($c['id']))) continue;
    $mod = !empty($c['created_at']) ? substr($c['created_at'], 0, 10) : $today;
    echo "  <url>\n";
    $curl = !empty($c['slug']) ? "/c/" . htmlspecialchars($c['slug']) : "/course.html?id=" . htmlspecialchars($c['id']);
    echo "    <loc>{$base}{$curl}</loc>\n";
    echo "    <lastmod>{$mod}</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.9</priority>\n";
    echo "  </url>\n";
}

// مقالات بلاگ
$posts = fetchRows('posts?select=slug,created_at&is_published=eq.true&order=created_at.desc&limit=200', $ANON_KEY, $SUPABASE_URL);
foreach ($posts as $p) {
    if (!is_array($p) || empty($p['slug'])) continue;
    $mod = !empty($p['created_at']) ? substr($p['created_at'], 0, 10) : $today;
    echo "  <url>\n";
    echo "    <loc>{$base}/p/" . htmlspecialchars($p['slug']) . "</loc>\n";
    echo "    <lastmod>{$mod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
