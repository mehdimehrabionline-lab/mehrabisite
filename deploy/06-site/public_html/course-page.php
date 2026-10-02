<?php
/**
 * صفحه‌ی دوره با عنوان/توضیح/OG سمت سرور (برای پیش‌نمایش لینک در تلگرام، واتساپ، گوگل)
 * فقط از کش محلی (api/cache/course_full.json) می‌خواند؛ هر مشکلی پیش بیاید خودِ course.html بدون تغییر نمایش داده می‌شود.
 * محتوا و عملکرد صفحه همان course.html است.
 */
$file = __DIR__ . '/course.html';
$html = @file_get_contents($file);
if ($html === false) { http_response_code(500); echo 'course.html not found'; exit; }

try {
    $slug = isset($_GET['slug']) ? preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['slug']) : '';
    $id   = isset($_GET['id']) ? preg_replace('/[^A-Za-z0-9-]/', '', $_GET['id']) : '';
    $course = null;
    $f = __DIR__ . '/api/cache/course_full.json';
    if (($slug !== '' || $id !== '') && is_file($f)) {
        $list = json_decode((string)file_get_contents($f), true);
        if (is_array($list)) foreach ($list as $c) {
            if (!is_array($c)) continue;
            if (($slug !== '' && ($c['slug'] ?? '') === $slug) || ($slug === '' && $id !== '' && ($c['id'] ?? '') === $id)) { $course = $c; break; }
        }
    }
    if ($course && !empty($course['title'])) {
        $e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $base = 'https://mehdimehrabi.ir';
        $title = trim($course['title']) . ' | مهدی محرابی';
        $desc = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($course['description'] ?? ''))));
        if (function_exists('mb_substr')) $desc = mb_substr($desc, 0, 200);
        if ($desc === '') $desc = 'دوره‌ی آموزشی ' . $course['title'] . ' — آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی';
        $url = $base . '/c/' . ($course['slug'] ?? $slug);
        $img = !empty($course['cover_url']) ? $course['cover_url'] : $base . '/assets/portrait.jpg';
        $html = preg_replace('#<title id="pageTitle">.*?</title>#su', '<title id="pageTitle">' . $e($title) . '</title>', $html, 1);
        $html = preg_replace('#<meta name="description" id="pageDesc" content="[^"]*"\s*/?>#u', '<meta name="description" id="pageDesc" content="' . $e($desc) . '"/>', $html, 1);
        $html = preg_replace('#<link rel="canonical" id="canonicalLink" href="[^"]*"\s*/?>#u', '<link rel="canonical" id="canonicalLink" href="' . $e($url) . '"/>', $html, 1);
        $og = '<meta property="og:type" content="website"/><meta property="og:locale" content="fa_IR"/><meta property="og:site_name" content="آکادمی مهدی محرابی"/>'
            . '<meta property="og:title" content="' . $e($course['title']) . '"/><meta property="og:description" content="' . $e($desc) . '"/>'
            . '<meta property="og:url" content="' . $e($url) . '"/><meta property="og:image" content="' . $e($img) . '"/>'
            . '<meta name="twitter:card" content="summary_large_image"/><meta name="twitter:title" content="' . $e($course['title']) . '"/><meta name="twitter:description" content="' . $e($desc) . '"/><meta name="twitter:image" content="' . $e($img) . '"/>';
        $html = preg_replace('#</head>#i', $og . "\n</head>", $html, 1);
    }
} catch (Throwable $ex) { /* بی‌صدا: همان course.html خام */ }

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60');
echo $html;
