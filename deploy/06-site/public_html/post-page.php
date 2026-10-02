<?php
/**
 * صفحه‌ی مقاله با عنوان/توضیح/OG سمت سرور (برای پیش‌نمایش لینک در تلگرام، واتساپ، گوگل)
 * فقط از کش محلی (api/cache/posts.json) می‌خواند؛ هر مشکلی پیش بیاید خودِ blog-post.html بدون تغییر نمایش داده می‌شود.
 * محتوا و عملکرد صفحه همان blog-post.html است.
 */
$file = __DIR__ . '/blog-post.html';
$html = @file_get_contents($file);
if ($html === false) { http_response_code(500); echo 'blog-post.html not found'; exit; }

try {
    $slug = isset($_GET['slug']) ? preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['slug']) : '';
    $id   = isset($_GET['id']) ? preg_replace('/[^A-Za-z0-9-]/', '', $_GET['id']) : '';
    $post = null;
    $f = __DIR__ . '/api/cache/posts.json';
    if (($slug !== '' || $id !== '') && is_file($f)) {
        $list = json_decode((string)file_get_contents($f), true);
        if (is_array($list)) foreach ($list as $c) {
            if (!is_array($c)) continue;
            if (($slug !== '' && ($c['slug'] ?? '') === $slug) || ($slug === '' && $id !== '' && ($c['id'] ?? '') === $id)) { $post = $c; break; }
        }
    }
    if ($post && !empty($post['title'])) {
        $e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $base = 'https://mehdimehrabi.ir';
        $title = trim($post['title']) . ' | مهدی محرابی';
        $desc = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($post['excerpt'] ?? ''))));
        if (function_exists('mb_substr')) $desc = mb_substr($desc, 0, 200);
        if ($desc === '') $desc = $post['title'] . ' — آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی';
        $url = $base . '/p/' . ($post['slug'] ?? $slug);
        $img = !empty($post['cover_url']) ? $post['cover_url'] : $base . '/assets/portrait.jpg';
        $html = preg_replace('#<title id="pageTitle">.*?</title>#su', '<title id="pageTitle">' . $e($title) . '</title>', $html, 1);
        $html = preg_replace('#<meta name="description" id="pageDesc" content="[^"]*"\s*/?>#u', '<meta name="description" id="pageDesc" content="' . $e($desc) . '"/>', $html, 1);
        $html = preg_replace('#<link rel="canonical" id="canonicalLink" href="[^"]*"\s*/?>#u', '<link rel="canonical" id="canonicalLink" href="' . $e($url) . '"/>', $html, 1);
        $og = '<meta property="og:type" content="article"/><meta property="og:locale" content="fa_IR"/><meta property="og:site_name" content="آکادمی مهدی محرابی"/>'
            . '<meta property="og:title" content="' . $e($post['title']) . '"/><meta property="og:description" content="' . $e($desc) . '"/>'
            . '<meta property="og:url" content="' . $e($url) . '"/><meta property="og:image" content="' . $e($img) . '"/>'
            . '<meta name="twitter:card" content="summary_large_image"/><meta name="twitter:title" content="' . $e($post['title']) . '"/><meta name="twitter:description" content="' . $e($desc) . '"/><meta name="twitter:image" content="' . $e($img) . '"/>';
        $html = preg_replace('#</head>#i', $og . "\n</head>", $html, 1);
    }
} catch (Throwable $ex) { /* بی‌صدا: همان blog-post.html خام */ }

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60');
echo $html;
