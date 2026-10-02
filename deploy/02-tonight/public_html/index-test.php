<?php
/**
 * محتوای متنیِ ذخیره‌شده در پنل ادمین رو همین‌جا، سمت سرور، می‌خونیم
 * تا از همون اولین نمایش صفحه درست باشه — بدون تأخیر یا پرش متن
 */
$__sc = [];
$__cacheFile = __DIR__ . '/api/cache/site_content.json';
if (file_exists($__cacheFile)) {
    $__rows = json_decode(file_get_contents($__cacheFile), true);
    if (is_array($__rows)) {
        foreach ($__rows as $__r) {
            if (isset($__r['key'])) $__sc[$__r['key']] = $__r['value'] ?? '';
        }
    }
}
function sc($key, $default) {
    global $__sc;
    $v = isset($__sc[$key]) ? trim($__sc[$key]) : '';
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : $default;
}
// عنوان هیرو — اگه ادمین بدون تگ رنگ نوشته باشه، خودکار آخرین کلمه رو تأکیدی می‌کنیم
function scHeroTitle($default) {
    global $__sc;
    $v = isset($__sc['hero_title']) ? trim($__sc['hero_title']) : '';
    if ($v === '') return $default;
    if (stripos($v, '<em>') !== false) return $v; // ادمین خودش تگ گذاشته
    $parts = preg_split('/\s+/u', $v);
    if (count($parts) > 1) {
        $last = array_pop($parts);
        return htmlspecialchars(implode(' ', $parts), ENT_QUOTES, 'UTF-8') . ' <em>' . htmlspecialchars($last, ENT_QUOTES, 'UTF-8') . '</em>';
    }
    return '<em>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</em>';
}
// نشان‌های اعتماد اضافی (آرایه JSON)
function scExtraBadges() {
    global $__sc;
    $v = isset($__sc['hero_badges_extra']) ? trim($__sc['hero_badges_extra']) : '';
    if ($v === '') return '';
    $arr = json_decode($v, true);
    if (!is_array($arr)) return '';
    $out = '';
    foreach ($arr as $t) {
        $out .= '<span class="hero-eyebrow-badge">' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    return $out;
}

// ── داده‌های بخش‌ها (دوره‌ها، نمونه‌کارها، سؤالات، نظرات) رو هم همین‌جا از کش محلی می‌خونیم ──
// نتیجه: صفحه برای نمایش دوره‌ها هیچ درخواست اضافه‌ای به سرور نمی‌زنه و فوراً کامل نشون داده می‌شه.
define('MM_CACHE_LIB', 1);
$__mmLib = __DIR__ . '/api/cache.php';
// فقط اگه نسخه‌ی جدید cache.php روی هاست باشه لودش می‌کنیم (نسخه‌ی قدیمی رو نادیده می‌گیریم)
if (is_file($__mmLib) && strpos((string)@file_get_contents($__mmLib), 'mm_cache_types') !== false) {
    require_once $__mmLib;
}
$__DATA = [];
if (function_exists('mm_cache_read')) {
    foreach (['site_content', 'courses', 'projects', 'testimonials', 'faqs'] as $__t) {
        $__d = mm_cache_read($__t);
        if (is_array($__d)) { $__DATA[$__t] = $__d; }
    }
}
header('Cache-Control: no-cache');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta name="robots" content="noindex,nofollow">
<script async src="https://www.googletagmanager.com/gtag/js?id=G-FKY77DD6ZV"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","G-FKY77DD6ZV");</script>

<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<!-- ===== SEO ===== -->
<!-- BUILD: 2026-06-06-v3 (dark theme + gemini assistant + new contact) -->
<title>آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی | بهترین دوره آموزش هوش مصنوعی در شیراز و ایران</title>
<meta name="description" content="مهدی محرابی — بنیان‌گذار آکادمی هوش مصنوعی و تولید محتوا در شیراز. دوره‌های آموزش تخصصی تولید محتوا با هوش مصنوعی و تولید محتوای پیشرفته با موبایل." />
<meta name="keywords" content="آکادمی هوش مصنوعی مهدی محرابی, آکادمی تولید محتوا مهدی محرابی, دوره جامع آموزش هوش مصنوعی, آموزش هوش مصنوعی شیراز, آموزش هوش مصنوعی در ایران, بهترین دوره آموزش هوش مصنوعی در ایران, بهترین دوره آموزش هوش مصنوعی, هوش مصنوعی برای کودکان و نوجوانان, دوره آموزش هوش مصنوعی برای کودکان و نوجوانان, دوره تخصصی آموزش هوش مصنوعی برای کودکان و نوجوانان, تولید محتوا با هوش مصنوعی, تولید محتوا با هوش مصنوعی در شیراز, دوره جامع آموزش هوش مصنوعی شیراز, دوره جامع آموزش هوش مصنوعی تهران, دوره جامع آموزش هوش مصنوعی ایران, متخصص هوش مصنوعی در شیراز, حرفه‌ای‌ترین شخص هوش مصنوعی در شیراز, مهدی محرابی, آموزش هوش مصنوعی, ویدیوگرافی شیراز, فیلم‌برداری شیراز, تبلیغات ویدیویی شیراز, متخصص تبلیغات در شیراز, دوره تصویربرداری و تدوین با موبایل, آموزش تصویربرداری و تدوین با موبایل, آموزش موبایلگرافی شیراز, آموزش ویدیو شیراز, ساخت فیلم با هوش مصنوعی, هوش مصنوعی در معماری و ساختمان, کانتنت کریتور شیراز, تولید محتوا در شیراز" />
<meta name="author" content="مهدی محرابی" />
<meta name="robots" content="index, follow, max-image-preview:large" />
<meta name="language" content="Persian" />
<meta name="geo.region" content="IR-07" />
<meta name="geo.placename" content="Shiraz" />
<link rel="canonical" href="https://mehdimehrabi.ir/" />

<!-- ===== Open Graph (برای واتساپ/تلگرام/اینستاگرام/لینکدین) ===== -->
<meta property="og:type" content="website" />
<meta property="og:locale" content="fa_IR" />
<meta property="og:site_name" content="آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی" />
<link rel="manifest" href="manifest.json"/>
<meta name="theme-color" content="#c98a5a"/>
<meta name="mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
<meta name="apple-mobile-web-app-title" content="آکادمی مهدی محرابی"/>
<meta property="og:image:width" content="1200"/>
<meta property="og:image:height" content="630"/>
<meta property="og:title" content="آکادمی هوش مصنوعی مهدی محرابی" />
<meta property="og:description" content="دوره‌های آموزش تخصصی تولید محتوا با هوش مصنوعی و تولید محتوای پیشرفته با موبایل" />
<meta property="og:url" content="https://mehdimehrabi.ir/" />
<meta property="og:image" content="https://mehdimehrabi.ir/assets/logo-512.png" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="آکادمی هوش مصنوعی مهدی محرابی" />
<meta name="twitter:description" content="دوره‌های آموزش تخصصی تولید محتوا با هوش مصنوعی و تولید محتوای پیشرفته با موبایل" />
<meta name="twitter:image" content="https://mehdimehrabi.ir/assets/logo-512.png" />

<!-- ===== داده‌ی ساختاریافته برای گوگل و چت‌بات‌های هوش مصنوعی ===== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "EducationalOrganization",
      "@id": "https://mehdimehrabi.ir/#academy",
      "name": "آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی",
      "alternateName": ["آکادمی هوش مصنوعی مهدی محرابی", "آکادمی تولید محتوا مهدی محرابی", "Mehdi Mehrabi AI Academy"],
      "image": "https://mehdimehrabi.ir/assets/portrait.jpg",
      "url": "https://mehdimehrabi.ir/",
      "telephone": "+989170073010",
      "email": "mehdimehrabionline@gmail.com",
      "description": "بهترین آکادمی آموزش هوش مصنوعی و تولید محتوا در شیراز. دوره‌های آموزش تخصصی تولید محتوا با هوش مصنوعی و تولید محتوای پیشرفته با موبایل.",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "شیراز",
        "addressRegion": "فارس",
        "addressCountry": "IR"
      },
      "areaServed": [
        { "@type": "City", "name": "شیراز" },
        { "@type": "State", "name": "فارس" },
        { "@type": "Country", "name": "ایران" }
      ],
      "founder": { "@id": "https://mehdimehrabi.ir/#person" },
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "دوره‌های آموزشی",
        "itemListElement": [
          {
            "@type": "Course",
            "name": "دوره جامع آموزش هوش مصنوعی",
            "description": "کامل‌ترین دوره آموزش هوش مصنوعی در ایران. مناسب برای همه سطوح.",
            "provider": { "@id": "https://mehdimehrabi.ir/#academy" },
            "inLanguage": "fa",
            "educationalLevel": "All levels",
            "keywords": ["هوش مصنوعی", "AI", "آموزش هوش مصنوعی", "شیراز"]
          },
          {
            "@type": "Course",
            "name": "دوره آموزشی تولید محتوا با موبایل",
            "description": "تصویربرداری و تدوین حرفه‌ای با موبایل همراه با تکنیک‌های کاربردی.",
            "provider": { "@id": "https://mehdimehrabi.ir/#academy" },
            "inLanguage": "fa",
            "keywords": ["تولید محتوا", "موبایلگرافی", "تصویربرداری", "تدوین"]
          },
          {
            "@type": "Course",
            "name": "دوره جامع هوش مصنوعی برای کودکان و نوجوانان",
            "description": "آموزش تخصصی هوش مصنوعی مخصوص سنین ۹ تا ۱۶ سال.",
            "provider": { "@id": "https://mehdimehrabi.ir/#academy" },
            "inLanguage": "fa",
            "typicalAgeRange": "9-16",
            "keywords": ["هوش مصنوعی کودکان", "آموزش هوش مصنوعی نوجوانان", "کودکان و هوش مصنوعی"]
          },
          {
            "@type": "Course",
            "name": "دوره آموزشی هوش مصنوعی در معماری و ساختمان",
            "description": "کاربرد هوش مصنوعی در معماری، طراحی داخلی و صنعت ساختمان.",
            "provider": { "@id": "https://mehdimehrabi.ir/#academy" },
            "inLanguage": "fa",
            "keywords": ["هوش مصنوعی معماری", "AI در ساختمان", "طراحی با هوش مصنوعی"]
          }
        ]
      }
    },
    {
      "@type": "ProfessionalService",
      "@id": "https://mehdimehrabi.ir/#business",
      "name": "آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی",
      "image": "https://mehdimehrabi.ir/assets/portrait.jpg",
      "url": "https://mehdimehrabi.ir/",
      "telephone": "+989170073010",
      "email": "mehdimehrabionline@gmail.com",
      "description": "متخصص هوش مصنوعی و تولید محتوا در شیراز. ارائه خدمات ویدیوگرافی، تبلیغات ویدیویی، ساخت فیلم با هوش مصنوعی و دوره‌های آموزشی.",
      "priceRange": "$$",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "شیراز",
        "addressRegion": "فارس",
        "addressCountry": "IR"
      },
      "areaServed": [
        { "@type": "City", "name": "شیراز" },
        { "@type": "Country", "name": "ایران" }
      ],
      "founder": { "@id": "https://mehdimehrabi.ir/#person" },
      "makesOffer": [
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "دوره جامع آموزش هوش مصنوعی" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "آموزش هوش مصنوعی شیراز" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "دوره هوش مصنوعی برای کودکان و نوجوانان" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "تولید محتوا با هوش مصنوعی" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "ویدیوگرافی و فیلم‌برداری شیراز" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "تبلیغات ویدیویی شیراز" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "ساخت فیلم با هوش مصنوعی" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "آموزش موبایلگرافی شیراز" } },
        { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "هوش مصنوعی در معماری و ساختمان" } }
      ]
    },
    {
      "@type": "Person",
      "@id": "https://mehdimehrabi.ir/#person",
      "name": "مهدی محرابی",
      "alternateName": "Mehdi Mehrabi",
      "jobTitle": "متخصص هوش مصنوعی، مدرس و تولیدکننده محتوا",
      "description": "مهدی محرابی بنیان‌گذار آکادمی هوش مصنوعی و تولید محتوا در شیراز. حرفه‌ای‌ترین متخصص هوش مصنوعی در شیراز با سابقه آموزش به صدها دانشجو در سراسر ایران.",
      "image": "https://mehdimehrabi.ir/assets/portrait.jpg",
      "url": "https://mehdimehrabi.ir/",
      "telephone": "+989170073010",
      "email": "mehdimehrabionline@gmail.com",
      "worksLocation": { "@type": "Place", "name": "شیراز، ایران" },
      "knowsAbout": [
        "هوش مصنوعی", "آموزش هوش مصنوعی", "تولید محتوا", "تولید محتوا با هوش مصنوعی",
        "ویدیوگرافی", "فیلم‌برداری", "تدوین", "تبلیغات ویدیویی", "موبایلگرافی",
        "ساخت فیلم با هوش مصنوعی", "هوش مصنوعی برای کودکان", "هوش مصنوعی در معماری",
        "Artificial Intelligence", "Content Creation", "Videography"
      ],
      "sameAs": [
        "https://instagram.com/Filmehdy"
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "بهترین دوره آموزش هوش مصنوعی در شیراز کجاست؟",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "آکادمی هوش مصنوعی و تولید محتوای مهدی محرابی در شیراز، یکی از بهترین و کامل‌ترین مراکز آموزش هوش مصنوعی در ایران است. دوره‌ها به‌صورت آنلاین در سراسر ایران برگزار می‌شود. برای اطلاعات بیشتر با شماره ۰۹۱۷۰۰۷۳۰۱۰ تماس بگیرید."
          }
        },
        {
          "@type": "Question",
          "name": "آیا دوره هوش مصنوعی برای کودکان و نوجوانان در شیراز وجود دارد؟",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "بله، مهدی محرابی دوره تخصصی آموزش هوش مصنوعی برای کودکان و نوجوانان ۹ تا ۱۶ سال در شیراز برگزار می‌کند. برای ثبت‌نام با ۰۹۱۷۰۰۷۳۰۱۰ تماس بگیرید."
          }
        },
        {
          "@type": "Question",
          "name": "متخصص هوش مصنوعی در شیراز کیست؟",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "مهدی محرابی، بنیان‌گذار آکادمی هوش مصنوعی و تولید محتوا در شیراز، یکی از شناخته‌شده‌ترین متخصصان هوش مصنوعی در شیراز و فارس است. ارائه خدمات آموزشی، تولید محتوا و مشاوره در حوزه هوش مصنوعی. تماس: ۰۹۱۷۰۰۷۳۰۱۰"
          }
        },
        {
          "@type": "Question",
          "name": "چطور می‌توانم در دوره‌های مهدی محرابی ثبت‌نام کنم؟",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "برای ثبت‌نام در دوره‌های آموزش هوش مصنوعی و تولید محتوای مهدی محرابی، با شماره ۰۹۱۷۰۰۷۳۰۱۰ (تلفن، واتساپ و تلگرام) تماس بگیرید یا فرم درخواست را در سایت mehdimehrabi.ir پر کنید."
          }
        }
      ]
    }
  ]
}
</script>


<link rel="preconnect" href="https://rknsiuyfhxnmhnfesfhh.supabase.co" crossorigin>
<link rel="dns-prefetch" href="https://rknsiuyfhxnmhnfesfhh.supabase.co">
<script>window.__DATA = <?php echo (json_encode($__DATA, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?: '[]'); ?>;</script>
<script defer src="/assets/supabase.min.js"></script>
<script defer src="supabase-config.js"></script>
<link rel="preload" href="/assets/fonts/vazirmatn-arabic-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/fonts/fonts.css">

<style>
/* ---------- Tokens ---------- */
:root{
  --bg:#0c0b09;            /* warm near-black (مثل بخش دستیار) */
  --bg-2:#15130f;          /* پنل تیره */
  --ink:#f4f1ea;           /* متن کرم روشن */
  --ink-2:#d8d3c8;
  --muted:#8f897e;
  --line:rgba(244,241,234,.12);
  --line-2:rgba(244,241,234,.20);
  --accent:#c98a5a;        /* terracotta روشن‌تر برای کنتراست روی تیره */
  --accent-2:#a85a32;
  --grain-op:.05;
  --serif:'Fraunces', 'Vazirmatn', serif;
  --sans:'Vazirmatn', system-ui, sans-serif;
}

/* ---------- ویدیوی پس‌زمینه کل سایت (کم‌رنگ، محدود به دو بخش اول) ---------- */
.bg-video-stage{position:fixed;inset:0;z-index:-1;overflow:hidden;background:var(--bg)}
.bg-video-stage video{width:100%;height:100%;object-fit:cover;opacity:.16;filter:grayscale(100%) contrast(1.05);will-change:opacity}
.bg-video-stage::after{
  content:"";position:absolute;inset:0;
  background:linear-gradient(180deg, rgba(0,0,0,.25) 0%, rgba(0,0,0,0) 30%, rgba(0,0,0,0) 55%, var(--bg) 100%);
}
.bg-video-veil{position:fixed;inset:0;z-index:-1;background:var(--bg);opacity:0;pointer-events:none}

*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--sans);
  background:var(--bg);
  color:var(--ink);
  overflow-x:hidden;
  line-height:1.7;
  font-weight:400;
  -webkit-font-smoothing:antialiased;
  cursor:default;
}

::selection{background:var(--ink);color:var(--bg)}

/* film grain */
body::before{
  content:"";position:fixed;inset:0;pointer-events:none;z-index:9000;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2' seed='4'/><feColorMatrix values='0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 .6 0'/></filter><rect width='100%' height='100%' filter='url(%23n)' opacity='.5'/></svg>");
  opacity:var(--grain-op);mix-blend-mode:screen;
}

/* ── نوار تبلیغاتی ── */
.promo-bar{background:linear-gradient(90deg,var(--accent-2),var(--accent));color:#fff;text-align:center;padding:10px 44px 10px 16px;font-size:13px;position:relative;z-index:200}
.promo-bar a{color:#fff;text-decoration:underline;text-underline-offset:3px;font-weight:600;margin-right:10px;white-space:nowrap}
.promo-close{position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;font-size:18px;cursor:pointer;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.promo-close:hover{background:rgba(255,255,255,.15)}
@media(max-width:600px){.promo-bar{font-size:12px;padding:10px 40px 10px 12px}}

/* ---------- Nav (sticky, blurred) ---------- */
.nav{
  position:sticky;top:0;z-index:260;
  display:flex;justify-content:space-between;align-items:center;
  color:var(--ink);
}
/* نوار داخلی — همین‌جا با اسکرول مخفی/نمایان می‌شه.
   ul#navMenu عمداً بیرون از این عنصره: چون transform و backdrop-filter هر دو باعث می‌شن
   هر position:fixed داخلشون دیگه نسبت به کل صفحه موقعیت‌یابی نشه (این یه رفتار رسمی CSS ـه
   که مرورگرهای جدید دقیق‌تر اجرا می‌کنن) — با نگه‌داشتن ul بیرون، این مشکل دیگه هیچ‌وقت رخ نمی‌ده */
.nav-inner{
  position:relative;flex:1;
  display:flex;justify-content:space-between;align-items:center;
  padding:18px 40px;
  transition:transform .35s ease;
}
.nav-inner.nav-hidden{transform:translateY(-100%)}
/* دسکتاپ: گزینه‌ها بخشی از خود نوارن → پس‌زمینه و مخفی‌شدن روی کل نوار اعمال می‌شه
   (منوی کشویی فقط روی موبایله، پس اینجا مشکل position:fixed پیش نمیاد) */
@media (min-width:961px){
  .nav{padding:18px 40px;background:rgba(12,11,9,.92);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--line);transition:transform .35s ease}
  .nav.nav-hidden{transform:translateY(-100%)}
  .nav-inner{flex:0 0 auto;padding:0}
  .nav-inner.nav-hidden{transform:none}
  .nav-bg{display:none}
}
.nav-bg{position:absolute;inset:0;z-index:-1;background:rgba(12,11,9,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
.nav-backdrop{position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.65);opacity:0;pointer-events:none;transition:opacity .3s ease}
.nav-backdrop.show{opacity:1;pointer-events:auto}
.nav .brand{
  font-family:var(--serif);font-weight:400;font-style:italic;font-size:21px;letter-spacing:.5px;
}
.nav .brand b{font-style:normal;font-weight:500}
#navMenu{display:flex;gap:28px;list-style:none;align-items:center}
.nav a{
  color:var(--ink-2);text-decoration:none;font-size:13px;letter-spacing:.4px;
  position:relative;padding:6px 0;font-weight:400;
}
.nav a::after{
  content:"";position:absolute;bottom:0;right:0;height:1px;width:0;
  background:currentColor;transition:width .35s ease;
}
#navAuthBtn{
  background:rgba(201,138,90,.14);border:1px solid rgba(201,138,90,.4);
  padding:8px 18px !important;border-radius:20px;font-weight:600 !important;
  color:var(--accent) !important;transition:all .3s ease;
}
#navAuthBtn:hover{background:var(--accent);color:#0a0a0a !important;border-color:var(--accent)}
#navAuthBtn::after{display:none}
.nav-actions{display:flex;align-items:center;gap:10px}
.nav-auth-mobile{display:none}
.nav a:hover{color:var(--accent)}
.nav a:hover::after{width:100%}
.nav .menu-btn{display:none;background:none;border:none;color:var(--ink);font-size:22px;cursor:pointer}
.lang-toggle{
  background:transparent;border:1px solid var(--line-2);color:var(--ink-2);
  padding:5px 12px;border-radius:20px;font-family:var(--sans);font-size:12px;
  letter-spacing:1px;cursor:pointer;transition:all .25s;opacity:.85;
}
.lang-toggle:hover{opacity:1;border-color:var(--accent);color:var(--accent)}

/* ---------- Hero (compact, split) ---------- */
.hero-stage{padding:64px 60px 72px;max-width:1300px;margin:0 auto}
.hero-sticky{display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center}
.hero-inner{position:relative;z-index:2}
.hero-eyebrow-badge{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--accent);background:rgba(201,138,90,.12);border:1px solid rgba(201,138,90,.35);padding:6px 14px;border-radius:20px;margin-bottom:20px}
.hero-badges-row{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
.hero-badges-row .hero-eyebrow-badge{margin-bottom:0}
.hero .tag,.hero-stage .tag{
  font-size:11px;letter-spacing:5px;opacity:.6;margin-bottom:20px;
  display:flex;align-items:center;gap:14px;
}
.hero-stage .tag::before{content:"";width:36px;height:1px;background:currentColor;display:inline-block}
.hero-stage h1{
  font-family:var(--serif);
  font-weight:300;
  font-size:clamp(32px, 4.2vw, 56px);
  line-height:1.18;letter-spacing:-.01em;
  margin-bottom:24px;
}
.hero-stage h1 em{font-style:italic;font-weight:300;color:var(--accent-2)}
.hero-stage .lead{max-width:52ch;font-size:16px;opacity:.88;line-height:1.85;margin-bottom:32px}
.hero-actions{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:8px}
.btn-primary{background:var(--accent);color:#0a0a0a;border:none;padding:15px 30px;border-radius:8px;font-family:var(--sans);font-size:14px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;cursor:pointer;transition:background .2s}
.btn-primary:hover{background:var(--accent-2);color:#fff}
.btn-secondary{background:transparent;color:var(--ink);border:1px solid var(--line-2);padding:15px 26px;border-radius:8px;font-family:var(--sans);font-size:14px;text-decoration:none;display:inline-flex;align-items:center}
.btn-secondary:hover{border-color:var(--accent);color:var(--accent)}
.hero-trust-row{display:flex;gap:28px;flex-wrap:wrap;margin-top:32px}
.hero-trust-item{display:flex;flex-direction:column;gap:2px}
.hero-trust-item .n{font-family:var(--serif);font-size:24px;color:var(--accent)}
.hero-trust-item .l{font-size:12px;color:var(--ink-2);opacity:.85}

/* ── ویدیوی هیرو (حلقه‌ای، بدون اسکرول‌اسکراب) ── */
.hero-media{position:relative;border-radius:16px;overflow:hidden;aspect-ratio:4/5;background:var(--bg-2);border:1px solid var(--line)}
.hero-media img,.hero-media video{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.hero-media video{filter:grayscale(20%) contrast(1.03)}
.hero-media img{display:none}
.hero-media.video-failed img{display:block}
.hero-media.video-failed video{display:none}

@media(max-width:900px){
  .hero-stage{padding:40px 20px 48px}
  .hero-sticky{grid-template-columns:1fr;gap:28px}
  .hero-media{order:-1;aspect-ratio:16/10}
}

@keyframes cue{0%,100%{transform:scaleY(.3);transform-origin:top}50%{transform:scaleY(1);transform-origin:top}}


/* ---------- Section base ---------- */
section{position:relative;z-index:2}
.content{background:var(--bg);position:relative;z-index:2}
.section{padding:140px 60px;max-width:1400px;margin:0 auto;position:relative}
.eyebrow{
  display:inline-flex;align-items:center;gap:14px;
  font-size:11px;letter-spacing:6px;color:var(--accent);
  margin-bottom:28px;font-weight:500;
}
.eyebrow::before{content:"";width:32px;height:1px;background:var(--accent)}
.section h2{
  font-family:var(--serif);font-weight:300;
  font-size:clamp(30px,4.4vw,58px);line-height:1.1;letter-spacing:-.02em;
  margin-bottom:48px;
}
.section h2 em{font-style:italic;color:var(--accent)}

/* ---------- About ---------- */
.about-grid{
  display:grid;grid-template-columns:5fr 7fr;gap:80px;align-items:start;
}
.about-portrait{position:relative}
.about-portrait img{
  width:100%;display:block;filter:grayscale(100%) contrast(1.05);
  border-radius:2px;
}
.about-portrait::before{
  content:"";position:absolute;inset:-14px;border:1px solid var(--line);
  border-radius:2px;z-index:-1;
}
.about-portrait .meta{
  position:absolute;bottom:-50px;right:14px;
  font-family:var(--serif);font-style:italic;font-size:13px;color:var(--muted);
  direction:ltr;text-align:right;
}
.about-text p{font-size:17px;color:var(--ink-2);margin-bottom:22px;max-width:560px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:50px;max-width:520px}
.stats .l{white-space:nowrap}
.stat{border-top:1px solid var(--line);padding-top:18px}
.stat .n{font-family:var(--serif);font-size:42px;font-weight:300;line-height:1}
.stat .l{font-size:12px;color:var(--muted);letter-spacing:1px;margin-top:6px}

/* ---------- Portfolio grids ---------- */
.work-filter{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px;margin-bottom:8px}
.wf-btn{background:transparent;border:1px solid var(--line);color:var(--muted);padding:6px 16px;border-radius:20px;font-family:var(--sans);font-size:12px;cursor:pointer;transition:all .25s}
.wf-btn:hover{border-color:var(--line-2);color:var(--ink)}
.wf-btn.active{background:var(--accent);color:#0a0a0a;border-color:var(--accent)}
.work-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.work{
  position:relative;overflow:hidden;background:var(--bg-2);
  border-radius:6px;cursor:pointer;border:1px solid var(--line);
  transition:transform .5s cubic-bezier(.2,.7,.2,1),border-color .4s;
  display:flex;flex-direction:column;
}
.work:hover{transform:translateY(-6px);border-color:var(--line-2)}
.work .ph{
  width:100%;position:relative;overflow:hidden;
  background:linear-gradient(135deg,#1a1410 0%,#0a0a0a 100%);
}
/* نسبت ابعاد: افقی 16:9، عمودی 9:16 */
.work.landscape .ph{aspect-ratio:16/9}
.work.portrait .ph{aspect-ratio:9/16}
.work:not(.landscape):not(.portrait) .ph{aspect-ratio:16/9}
.work .ph .label{
  position:absolute;bottom:0;right:0;left:0;z-index:3;
  padding:20px 18px 16px;
  background:linear-gradient(0deg,rgba(0,0,0,.8) 0%,transparent 100%);
}
.work .ph .label .t{font-family:var(--serif);font-size:18px;font-weight:400;margin-bottom:3px;color:#f4f1ea}
.work .ph .label .c{font-size:10px;letter-spacing:3px;opacity:.7;color:#f4f1ea}
/* توضیحات زیر کارت */
.work-desc{padding:14px 18px;font-size:13px;color:var(--muted);line-height:1.7;border-top:1px solid var(--line)}
/* تگ نوع کار */
.work-type-tag{position:absolute;top:12px;right:12px;z-index:4;font-size:9px;letter-spacing:2px;padding:3px 10px;border-radius:20px;font-weight:600}
.work-type-tag.ai{background:rgba(201,138,90,.85);color:#0a0a0a}
.work-type-tag.video{background:rgba(26,107,212,.85);color:#fff}
.work-type-tag.content{background:rgba(80,160,80,.85);color:#fff}

/* ---------- AI works (asymmetric mosaic) ---------- */
.ai-mosaic{display:grid;grid-template-columns:repeat(6,1fr);grid-auto-rows:140px;gap:18px}
.ai-tile{
  position:relative;overflow:hidden;border-radius:2px;
  background:#0a0a0a;color:#f4f1ea;border:1px solid var(--line);
}
.ai-tile .inner{
  position:absolute;inset:0;display:flex;align-items:flex-end;padding:20px;
}
.ai-tile .inner::before{
  content:"";position:absolute;inset:0;
  background-image:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.1), transparent 50%),
    url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='n'><feTurbulence baseFrequency='.65' numOctaves='3'/></filter><rect width='100%' height='100%' filter='url(%23n)' opacity='.35'/></svg>");
}
.ai-tile .name{position:relative;z-index:2;font-family:var(--serif);font-style:italic;font-size:18px}
.ai-tile .num{position:absolute;top:14px;right:18px;font-size:10px;letter-spacing:3px;opacity:.6;z-index:2}
.ai-tile.t1{grid-column:span 3;grid-row:span 3;background:linear-gradient(135deg,#1a1410,#a85a32)}
.ai-tile.t2{grid-column:span 3;grid-row:span 2;background:linear-gradient(135deg,#0a0a0a,#3a2a1c)}
.ai-tile.t3{grid-column:span 2;grid-row:span 2;background:linear-gradient(135deg,#181818,#2a1c10)}
.ai-tile.t4{grid-column:span 2;grid-row:span 3;background:linear-gradient(180deg,#1c1410,#0a0a0a)}
.ai-tile.t5{grid-column:span 2;grid-row:span 2;background:linear-gradient(135deg,#0a0a0a,#1a2028)}
.ai-tile.t6{grid-column:span 3;grid-row:span 2;background:linear-gradient(135deg,#1c1810,#a85a32)}
.ai-tile.t7{grid-column:span 3;grid-row:span 2;background:linear-gradient(135deg,#0a0a0a,#241a14)}

/* ---------- Dynamic media inside tiles ---------- */
.work-loading{grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted);font-family:var(--serif);font-style:italic;font-size:16px}
.tile-media{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;filter:grayscale(15%) contrast(1.02)}
.work .ph .tile-media{filter:grayscale(15%) contrast(1.02)}
.work .ph .scrim,.ai-tile .scrim{
  content:"";position:absolute;inset:0;z-index:1;
  background:linear-gradient(180deg, rgba(10,10,10,0) 40%, rgba(10,10,10,.75) 100%);
}
.ai-tile .name,.ai-tile .num,.work .ph .label{z-index:2}
.work .ph.has-media::before{display:none}
.ai-tile.has-media .inner::before{display:none}
.tile-empty-note{grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--muted);font-size:14px}
.video-thumb-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:1;background:rgba(0,0,0,.3);transition:background .3s}
.work:hover .video-thumb-overlay,.ai-tile:hover .video-thumb-overlay{background:rgba(0,0,0,.5)}
.play-btn-circle{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;font-size:20px;padding-left:4px;backdrop-filter:blur(4px);transition:transform .3s,background .3s}
.work:hover .play-btn-circle,.ai-tile:hover .play-btn-circle{transform:scale(1.12);background:rgba(201,138,90,.7);border-color:var(--accent)}
/* Lightbox */
@keyframes spin{to{transform:rotate(360deg)}}
.lb{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.92);align-items:center;justify-content:center;padding:20px}
.lb.open{display:flex}
.lb-inner{position:relative;width:100%;max-width:900px}
.lb-close{position:absolute;top:-44px;right:0;background:none;border:none;color:#fff;font-size:32px;cursor:pointer;line-height:1;padding:4px 12px;opacity:.7;transition:opacity .2s}
.lb-close:hover{opacity:1}
.lb-frame{width:100%;aspect-ratio:16/9;border:none;border-radius:6px;display:block}
.lb-notice{text-align:center;margin-top:12px;font-size:13px;padding:8px 16px;border-radius:6px}
.lb-notice.off{background:rgba(26,107,212,.15);color:#6ab0ff;border:1px solid rgba(26,107,212,.3)}
.lb-notice.on{background:rgba(224,0,0,.12);color:#ff8080;border:1px solid rgba(224,0,0,.25)}

.vpn-badge{position:absolute;top:12px;right:12px;font-size:10px;letter-spacing:1px;padding:3px 10px;border-radius:20px;font-weight:600;z-index:2}
.aparat-badge{background:#1a6bd4;color:#fff}
.youtube-badge{background:#e00;color:#fff}
/* VPN notice on blog/detail pages */
.vpn-notice{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:6px;font-size:13px;margin-top:10px}
.vpn-notice.off{background:rgba(26,107,212,.12);border:1px solid rgba(26,107,212,.3);color:#6ab0ff}
.vpn-notice.on{background:rgba(224,0,0,.1);border:1px solid rgba(224,0,0,.25);color:#ff8080}

/* ---------- Courses ---------- */
.section-lead{font-size:16px;color:var(--muted);max-width:520px;margin-top:-28px;margin-bottom:48px;line-height:1.85}
.courses-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
.course{
  background:var(--bg-2);border:1px solid var(--line);border-radius:4px;
  padding:36px;display:flex;flex-direction:column;gap:16px;position:relative;overflow:hidden;
  transition:transform .5s cubic-bezier(.2,.7,.2,1),border-color .4s;cursor:pointer;
}
.course::before{
  content:"";position:absolute;top:0;right:0;width:200px;height:200px;
  background:radial-gradient(circle at 100% 0%, rgba(201,138,90,.14), transparent 70%);
  pointer-events:none;
}
.course:hover{transform:translateY(-6px);border-color:var(--line-2)}
.course-top{display:flex;justify-content:space-between;align-items:center}
.course-tag{font-size:10px;letter-spacing:3px;color:var(--accent);border:1px solid var(--line-2);padding:4px 12px;border-radius:20px}
.course-num{font-family:var(--serif);font-size:30px;font-weight:300;color:var(--muted);opacity:.5}
.course h3{font-family:var(--serif);font-weight:400;font-size:26px;line-height:1.25;letter-spacing:-.01em}
.course p{font-size:15px;color:var(--ink-2);line-height:1.85;flex:1}
.course-foot{margin-top:8px}
.course-featured{font-size:9px;letter-spacing:2px;background:var(--accent);color:#0a0a0a;padding:2px 10px;border-radius:20px;font-weight:600}
.course-banner{width:calc(100% + 72px);margin:-36px -36px 20px;overflow:hidden;height:160px}
.course-banner img{width:100%;height:100%;object-fit:cover;filter:brightness(.9)}
.course-inner{position:relative}
.course-metas{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}
.cmeta-item{font-size:11px;color:var(--muted);background:rgba(244,241,234,.06);padding:4px 10px;border-radius:20px;border:1px solid var(--line)}
.course-price{font-family:var(--serif);font-size:22px;font-weight:300;color:var(--accent);margin-bottom:12px}
.course-price-orig{font-size:12px;color:var(--muted);text-decoration:line-through;margin-bottom:2px}
.course-discount-ribbon{position:absolute;top:14px;left:-6px;background:linear-gradient(135deg,#e0455a,#c9285a);color:#fff;font-size:11px;font-weight:700;padding:5px 14px;border-radius:0 20px 20px 0;box-shadow:0 3px 10px rgba(224,69,90,.4);z-index:2}
.course-foot{display:flex;justify-content:space-between;align-items:center;margin-top:auto;flex-wrap:wrap;gap:10px}
  color:var(--accent);text-decoration:none;font-size:14px;letter-spacing:1px;
  border-bottom:1px solid transparent;padding-bottom:3px;transition:border-color .25s;
}
.course-cta:hover{border-bottom-color:var(--accent)}

/* ---------- AI Assistant ---------- */
.assistant{
  background:#0a0a0a;color:#f4f1ea;padding:120px 60px;
  position:relative;overflow:hidden;
}
.assistant::before{
  content:"";position:absolute;inset:0;
  background:radial-gradient(circle at 80% 20%, rgba(168,90,50,.18) 0%, transparent 60%);
}
.assistant-inner{max-width:1100px;margin:0 auto;position:relative;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center}
.assistant .eyebrow{color:var(--accent-2)}
.assistant .eyebrow::before{background:var(--accent-2)}
.assistant h2{color:#f4f1ea;font-family:var(--serif);font-weight:300;font-size:clamp(30px,4.4vw,58px);line-height:1.1;letter-spacing:-.02em;margin-bottom:28px}
.assistant h2 em{color:var(--accent-2)}
.assistant p{color:rgba(244,241,234,.7);max-width:440px;margin-bottom:28px}
.chat-card{
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);
  border-radius:8px;padding:24px;backdrop-filter:blur(20px);
  display:flex;flex-direction:column;height:480px;
}
.chat-head{
  display:flex;align-items:center;gap:12px;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,.08);
}
.chat-dot{width:8px;height:8px;border-radius:50%;background:#7ec77e;box-shadow:0 0 12px #7ec77e}
.chat-head .t{font-family:var(--serif);font-style:italic;font-size:16px}
.chat-head .s{font-size:11px;color:rgba(244,241,234,.5);margin-right:auto}
.chat-body{flex:1;overflow-y:auto;padding:18px 4px;display:flex;flex-direction:column;gap:14px}
.chat-body::-webkit-scrollbar{width:4px}
.chat-body::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px}
.msg{max-width:85%;padding:12px 16px;border-radius:14px;font-size:14px;line-height:1.6}
.msg.bot{background:rgba(255,255,255,.06);align-self:flex-start;border-top-right-radius:4px}
.msg.user{background:var(--accent);color:#fff;align-self:flex-end;border-top-left-radius:4px}
.msg.typing{display:flex;gap:4px;align-items:center;align-self:flex-start;background:rgba(255,255,255,.06);padding:14px 18px;border-radius:14px}
.msg.typing span{width:6px;height:6px;border-radius:50%;background:rgba(244,241,234,.6);animation:blink 1.2s infinite}
.msg.typing span:nth-child(2){animation-delay:.2s}
.msg.typing span:nth-child(3){animation-delay:.4s}
@keyframes blink{0%,80%,100%{opacity:.3;transform:scale(.8)}40%{opacity:1;transform:scale(1)}}
.chat-input{
  display:flex;gap:10px;padding-top:14px;border-top:1px solid rgba(255,255,255,.08);
}
.chat-input input{
  flex:1;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);
  color:#f4f1ea;padding:12px 16px;border-radius:24px;font-family:var(--sans);font-size:14px;
  outline:none;transition:border .2s;
}
.chat-input input:focus{border-color:var(--accent-2)}
.chat-input button{
  background:var(--accent);color:#fff;border:none;padding:0 22px;border-radius:24px;
  cursor:pointer;font-family:var(--sans);font-size:14px;transition:background .2s;
}
.chat-input button:hover{background:var(--accent-2)}
.chat-input button:disabled{opacity:.5;cursor:not-allowed}

/* ── Testimonials ── */
.testimonials-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;margin-top:40px}

/* ── سؤالات متداول ── */
.faq-inline{margin-top:64px;padding-top:48px;border-top:1px solid var(--line)}
.faq-inline h3{font-family:var(--serif);font-weight:300;font-size:24px;margin-bottom:8px}
.faq-inline h3 em{font-style:italic;color:var(--accent)}
.faq-list{display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:none;margin-top:24px}
.faq-item{background:var(--bg-2);border:1px solid var(--line);border-radius:10px;padding:18px 22px;align-self:start}
@media(max-width:760px){.faq-list{grid-template-columns:1fr}}
.faq-item summary{cursor:pointer;font-size:15px;font-weight:600;list-style:none;display:flex;justify-content:space-between;align-items:center;gap:12px}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::after{content:'+';color:var(--accent);font-size:20px;font-weight:400;transition:transform .2s;flex-shrink:0}
.faq-item[open] summary::after{transform:rotate(45deg)}
.faq-item p{color:var(--ink-2);font-size:14px;margin:14px 0 0;line-height:1.9}

/* ── دوره ویژه (spotlight) ── */
.featured-course{display:grid;grid-template-columns:1fr 1.4fr;gap:0;background:var(--bg-2);border:1px solid var(--accent);border-radius:16px;overflow:hidden;margin-bottom:36px}
.featured-course img{width:100%;height:100%;object-fit:cover;min-height:220px}
.featured-course .fc-body{padding:32px 36px;display:flex;flex-direction:column;justify-content:center}
.featured-course .ribbon{display:inline-flex;align-items:center;gap:6px;background:var(--accent);color:#0a0a0a;font-size:12px;font-weight:700;padding:6px 14px;border-radius:20px;width:fit-content;margin-bottom:14px}
.featured-course h3{font-family:var(--serif);font-weight:400;font-size:24px;margin:0 0 10px}
.featured-course p{color:var(--ink-2);font-size:14px;margin:0 0 18px;max-width:48ch}
.featured-course .price-row{display:flex;align-items:baseline;gap:12px;margin-bottom:20px}
.featured-course .price-old{color:var(--muted);text-decoration:line-through;font-size:13px}
.featured-course .price-new{font-family:var(--serif);font-size:24px;color:var(--accent)}
.courses-grid.courses-list{display:flex !important;flex-direction:column;gap:28px}
.courses-list .featured-course{margin-bottom:0;transition:border-color .25s,transform .25s}
.courses-list .featured-course:hover{transform:translateY(-2px)}
.featured-course .fc-ph{min-height:240px;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;padding:28px;background:linear-gradient(135deg,#2b2118,#15130f 70%);font-family:var(--serif);font-size:24px;line-height:1.5;color:var(--accent)}
.featured-course .fc-tag{display:inline-flex;width:fit-content;font-size:12px;color:var(--accent);border:1px solid rgba(201,138,90,.4);padding:5px 14px;border-radius:20px;margin-bottom:14px}
.featured-course .fc-metas{margin:0 0 14px}
.featured-course .fc-cta{display:inline-flex;align-items:center;gap:8px;background:var(--accent);color:#0a0a0a;padding:13px 26px;border-radius:8px;font-size:13px;font-weight:700;width:fit-content}
.featured-course:hover .fc-cta{background:var(--accent-2);color:#fff}
@media(max-width:820px){.featured-course{grid-template-columns:1fr}.featured-course .fc-body{padding:24px 20px}}

/* ── نوار خرید چسبان ── */

.tst-card{background:var(--bg-2);border:1px solid var(--line);border-radius:8px;padding:28px;transition:transform .4s,border-color .3s}
.tst-card:hover{transform:translateY(-4px);border-color:var(--line-2)}
.tst-head{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.tst-avatar{width:48px;height:48px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:20px;color:#0a0a0a;flex-shrink:0;overflow:hidden}
.tst-avatar img{width:100%;height:100%;object-fit:cover}
.tst-name{font-weight:500;font-size:15px;margin-bottom:2px}
.tst-role{font-size:12px;color:var(--muted)}
.tst-stars{color:var(--accent);font-size:13px;margin-bottom:12px;letter-spacing:2px}
.tst-content{font-size:14px;line-height:1.9;color:var(--ink-2)}
.tst-course{margin-top:14px;padding-top:14px;border-top:1px solid var(--line);font-size:11px;color:var(--muted);letter-spacing:1px}
/* ---------- Contact ---------- */
.contact{padding:120px 40px;background:var(--bg);position:relative;z-index:2}
.contact-inner{max-width:900px;margin:0 auto}
.contact-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:40px 0}
.contact-card{background:var(--bg-2);border:1px solid var(--line);border-radius:8px;padding:24px 20px;text-decoration:none;color:var(--ink);transition:transform .3s,border-color .3s;text-align:center}
.contact-card:hover{transform:translateY(-4px);border-color:var(--accent)}
.cc-icon{font-size:28px;margin-bottom:10px}
.cc-label{font-size:10px;letter-spacing:3px;color:var(--muted);margin-bottom:6px}
.cc-val{font-size:13px;color:var(--ink-2);word-break:break-all}
.contact-cta-row{display:flex;align-items:center;gap:24px;flex-wrap:wrap;margin-top:8px}
.contact-cta-btn{display:inline-flex;align-items:center;gap:8px;background:var(--accent);color:#0a0a0a;padding:14px 28px;border-radius:4px;text-decoration:none;font-size:14px;font-weight:600;transition:background .3s}
.contact-cta-btn:hover{background:var(--accent-2);color:#fff}
.contact-location{font-size:13px;color:var(--muted)}
.contact h2{font-family:var(--serif);font-size:clamp(30px,4.4vw,54px);font-weight:300;line-height:1.1;margin-bottom:32px}
.contact h2 em{font-style:italic;color:var(--accent)}
.contact .lead{color:var(--muted);font-size:16px;max-width:380px;margin-bottom:40px}
.contact-meta{font-size:14px;color:var(--ink-2);line-height:2}
.contact-meta a{color:var(--ink);text-decoration:none;border-bottom:1px solid var(--line);transition:border-color .2s}
.contact-meta a:hover{border-color:var(--accent)}
.contact-meta .lbl{display:block;font-size:11px;letter-spacing:3px;color:var(--muted);margin-top:18px;margin-bottom:4px}
.contact-social{display:flex;gap:10px;margin-top:28px;flex-wrap:wrap}
.social-btn{
  display:inline-block;padding:10px 20px;border:1px solid var(--line-2);border-radius:30px;
  color:var(--ink);text-decoration:none;font-size:13px;letter-spacing:1px;transition:all .25s;
}
.social-btn:hover{background:var(--accent);border-color:var(--accent);color:#0a0a0a}

form.order{display:grid;gap:18px}
.field{position:relative}
.field label{
  position:absolute;top:14px;right:0;font-size:13px;color:var(--muted);
  transition:all .25s ease;pointer-events:none;
}
.field input,.field textarea,.field select{
  width:100%;background:transparent;border:none;border-bottom:1px solid var(--line);
  padding:14px 0 10px;font-family:var(--sans);font-size:15px;color:var(--ink);
  outline:none;transition:border-color .25s;
}
.field textarea{min-height:90px;resize:vertical}
.field input:focus,.field textarea:focus,.field select:focus{border-bottom-color:var(--accent)}
.field input:focus+label,.field textarea:focus+label,
.field input:not(:placeholder-shown)+label,.field textarea:not(:placeholder-shown)+label{
  top:-10px;font-size:10px;letter-spacing:2px;color:var(--accent);
}
.field.sel select+label{top:-10px;font-size:10px;letter-spacing:2px;color:var(--accent)}
.field.sel{position:relative}
.field.sel::after{content:"▾";position:absolute;left:0;top:14px;color:var(--muted);pointer-events:none}
.btn-submit{
  margin-top:14px;background:var(--ink);color:var(--bg);border:none;
  padding:18px 28px;font-family:var(--sans);font-size:14px;letter-spacing:3px;
  cursor:pointer;display:flex;align-items:center;justify-content:space-between;
  transition:background .3s;
}
.btn-submit:hover{background:var(--accent)}
.btn-submit .arrow{font-family:var(--serif);font-size:20px}
.form-ok{
  padding:18px;background:rgba(168,90,50,.1);border:1px solid var(--accent);
  border-radius:2px;font-size:14px;display:none;
}
.form-ok.show{display:block}

/* ---------- Footer ---------- */
footer{
  background:#0a0a0a;color:#f4f1ea;padding:80px 60px 36px;position:relative;z-index:2;
}
.foot-inner{max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:flex-end;gap:40px;flex-wrap:wrap}
footer .big{font-family:var(--serif);font-size:clamp(40px,7vw,96px);font-weight:300;line-height:.9;letter-spacing:-.02em}
footer .big em{font-style:italic;color:var(--accent-2)}
.foot-meta{font-size:12px;color:rgba(244,241,234,.5);letter-spacing:1px;line-height:2}
.foot-meta a{color:rgba(244,241,234,.8);text-decoration:none;margin-left:14px}
.foot-meta a:hover{color:var(--accent-2)}
.pwa-install-wrap{max-width:1400px;margin:30px auto 0;text-align:center}
#pwaInstall{background:var(--accent);color:#0a0a0a;border:none;padding:12px 24px;border-radius:6px;font-family:var(--sans);font-size:14px;font-weight:600;cursor:pointer;align-items:center;gap:8px;transition:background .3s}
#pwaInstall:hover{background:var(--accent-2);color:#fff}
.enamad-wrap{max-width:1400px;margin:40px auto 0;display:flex;justify-content:center;padding-top:24px;border-top:1px solid rgba(255,255,255,.08)}
.enamad-wrap img{max-width:110px;height:auto;opacity:.9;transition:opacity .3s;background:#fff;border-radius:6px;padding:6px}
.enamad-wrap img:hover{opacity:1}
.foot-bot{max-width:1400px;margin:60px auto 0;padding-top:24px;border-top:1px solid rgba(255,255,255,.08);
  display:flex;justify-content:space-between;font-size:11px;color:rgba(244,241,234,.4);letter-spacing:2px}

/* ---------- Reveal animations ---------- */
.reveal{opacity:0;transform:translateY(40px);transition:opacity 1s ease,transform 1s cubic-bezier(.2,.7,.2,1)}
.reveal.in{opacity:1;transform:none}

/* ---------- Responsive ---------- */
@media (max-width:960px){
  .nav-inner{padding:16px 20px}
  .nav ul{
    position:fixed;top:0;right:0;bottom:0;width:80%;max-width:320px;z-index:250;
    background:#0c0b09;flex-direction:column;justify-content:flex-start;align-items:stretch;
    padding:90px 28px 40px;gap:0;transform:translateX(100%);transition:transform .35s ease;mix-blend-mode:normal;
    box-shadow:-8px 0 40px rgba(0,0,0,.5);border-left:1px solid var(--line);
    overflow-y:auto;text-align:right;direction:rtl;
  }
  .nav ul.open{transform:translateX(0)}
  .nav ul li{width:100%;border-bottom:1px solid var(--line)}
  .nav ul li:last-child{border-bottom:none}
  .nav ul a{
    display:block;width:100%;font-size:17px;color:#f4f1ea;padding:16px 2px;text-align:right;
  }
  .nav ul li:has(#navAuthBtn),.nav ul li:has(#langToggle){border-bottom:none;padding-top:14px}
  #navAuthBtn{display:inline-flex;margin-top:6px}
  #navAuthLi{display:none}
  .nav-auth-mobile{
    display:inline-flex;align-items:center;min-height:40px;white-space:nowrap;
    background:rgba(201,138,90,.14);border:1px solid rgba(201,138,90,.45);
    padding:8px 14px !important;border-radius:20px;font-size:13px;font-weight:600;
    color:var(--accent) !important;text-decoration:none;
  }
  .nav-auth-mobile::after{display:none !important}
  .nav .menu-btn{display:block;z-index:260;position:relative}
  .nav.menu-open{mix-blend-mode:normal}
  .hero-sticky{padding:0}
  .section{padding:90px 22px}
  .about-grid{grid-template-columns:1fr;gap:60px}
  .work-grid{grid-template-columns:repeat(6,1fr);gap:14px}
  .work.w1,.work.w2,.work.w3,.work.w4{grid-column:span 6}
  .work.w5,.work.w6,.work.w7{grid-column:span 3}
  .ai-mosaic{grid-template-columns:repeat(4,1fr);grid-auto-rows:110px}
  .courses-grid{grid-template-columns:1fr;gap:18px}
  .course{padding:28px}
  .section-lead{margin-top:-16px;margin-bottom:36px}
  .ai-tile.t1{grid-column:span 4;grid-row:span 2}
  .ai-tile.t2{grid-column:span 4;grid-row:span 2}
  .ai-tile.t3{grid-column:span 2;grid-row:span 2}
  .ai-tile.t4{grid-column:span 2;grid-row:span 2}
  .ai-tile.t5{grid-column:span 2;grid-row:span 2}
  .ai-tile.t6{grid-column:span 2;grid-row:span 2}
  .ai-tile.t7{grid-column:span 4;grid-row:span 2}
  .assistant{padding:90px 22px}
  .assistant-inner{grid-template-columns:1fr;gap:40px}
  .contact{padding:80px 22px}
  .contact-cards{grid-template-columns:repeat(2,1fr)}
  footer{padding:60px 22px 30px}
  .foot-bot{flex-direction:column;gap:10px}
  .stats{grid-template-columns:repeat(3,1fr)}
  .stat .n{font-size:30px}
}
@media (max-width:520px){
  .stats{grid-template-columns:repeat(3,1fr);gap:10px}
  .stats .n{font-size:26px}
  .stats .l{font-size:10px;letter-spacing:1px;white-space:nowrap}
  .work-grid{grid-template-columns:1fr;gap:14px}
  .work.w1,.work.w2,.work.w3,.work.w4,.work.w5,.work.w6,.work.w7{grid-column:span 1}
  .work .ph{aspect-ratio:5/4}
}
/* ---------- پاپ‌آپ فیلترشکن، بنر PWA، راهنمای iOS ---------- */
.vpn-popup,.ios-guide{
  position:fixed;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.7);backdrop-filter:blur(6px);
  opacity:0;pointer-events:none;transition:opacity .3s ease;padding:20px;
}
.vpn-popup.show,.ios-guide.show{opacity:1;pointer-events:auto}
.vpn-box,.ios-box{
  background:var(--bg-2);border:1px solid var(--line-2);border-radius:14px;
  max-width:400px;width:100%;padding:32px 28px;text-align:center;position:relative;
  box-shadow:0 20px 60px rgba(0,0,0,.5);
}
.vpn-box .icon{font-size:36px;margin-bottom:14px}
.vpn-box h3,.ios-box h3{font-family:var(--serif);font-weight:400;font-size:22px;margin-bottom:14px}
.vpn-box p,.ios-box p{font-size:13px;color:var(--ink-2);line-height:1.9;margin-bottom:22px}
.vpn-btn{background:var(--accent);color:#0a0a0a;border:none;padding:13px 28px;border-radius:8px;font-family:var(--sans);font-size:14px;font-weight:600;cursor:pointer;transition:background .2s}
.vpn-btn:hover{background:var(--accent-2);color:#fff}
.vpn-box .hint{font-size:11px;color:var(--muted);margin-top:14px}
.ios-close{position:absolute;top:14px;left:14px;background:none;border:1px solid var(--line-2);color:var(--muted);width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:14px}
.ios-steps{text-align:right;display:flex;flex-direction:column;gap:14px;margin-top:6px}
.ios-step{display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--ink-2);line-height:1.8}
.ios-step .num{flex-shrink:0;width:24px;height:24px;border-radius:50%;background:var(--accent);color:#0a0a0a;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}

.pwa-banner{
  position:fixed;bottom:16px;right:16px;left:16px;max-width:420px;margin:0 auto;z-index:400;
  background:var(--bg-2);border:1px solid var(--line-2);border-radius:12px;
  padding:14px 16px;display:flex;align-items:center;gap:12px;
  box-shadow:0 12px 36px rgba(0,0,0,.45);
  transform:translateY(140%);opacity:0;transition:transform .35s ease,opacity .35s ease;
}
.pwa-banner.show{transform:translateY(0);opacity:1}
.pwa-icon{width:44px;height:44px;border-radius:10px;flex-shrink:0}
.pwa-text{flex:1;display:flex;flex-direction:column;gap:2px;min-width:0}
.pwa-text strong{font-size:13px}
.pwa-text span{font-size:11px;color:var(--muted)}
.pwa-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.pwa-install{background:var(--accent);color:#0a0a0a;border:none;padding:9px 16px;border-radius:8px;font-family:var(--sans);font-size:12px;font-weight:700;cursor:pointer}
.pwa-install:hover{background:var(--accent-2);color:#fff}
.pwa-close{background:none;border:none;color:var(--muted);font-size:16px;cursor:pointer;width:28px;height:28px}
@media(max-width:480px){.pwa-banner{padding:12px}.pwa-text strong{font-size:12px}.pwa-text span{font-size:10px}}
</style>
<link rel="icon" href="assets/favicon.ico" sizes="any"/>
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png"/>
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png"/>
<link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png"/>
</head>
<body>
<div style="position:fixed;bottom:6px;left:6px;z-index:99999;background:#c98a5a;color:#000;padding:2px 9px;border-radius:10px;font:600 11px sans-serif;opacity:.9;pointer-events:none">TEST</div>

<!-- ============== نوار تبلیغاتی (از پنل ادمین قابل ویرایش) ============== -->
<div class="promo-bar" id="promoBar" style="display:none" role="region" aria-label="اطلاعیه ویژه">
  <span id="promoBarText"></span>
  <a href="#" id="promoBarLink"></a>
  <button class="promo-close" onclick="dismissPromo()" aria-label="بستن اطلاعیه">×</button>
</div>

<!-- Supabase client + config -->

<!-- ============== ویدیوی پس‌زمینه کل سایت — کم‌رنگ، فقط تا انتهای دو بخش اول ============== -->
<div class="bg-video-stage" id="bgVideoStage">
  <video id="bgScrollVideo" data-src="assets/bg.mp4" muted playsinline preload="none" webkit-playsinline></video>
</div>
<div class="bg-video-veil" id="bgVideoVeil"></div>

<!-- ============== NAV ============== -->
<header class="nav" id="nav">
  <div class="nav-inner" id="navInner">
    <div class="nav-bg" aria-hidden="true"></div>
    <a href="#home" class="brand" id="navBrandText"><?php echo sc('nav_brand','Mehdi <b>Mehrabi</b>'); ?></a>
    <div class="nav-actions">
      <a href="register.html" id="navAuthBtnMobile" class="nav-auth-mobile" data-i18n="nav_register">ورود / ثبت‌نام</a>
      <button class="menu-btn" id="menuBtn" aria-label="منو">≡</button>
    </div>
  </div>
  <ul id="navMenu">
    <li><a href="#home" data-i18n="nav_home">خانه</a></li>
    <li><a href="#courses" data-i18n="nav_courses">دوره‌ها</a></li>
    <li><a href="#about" data-i18n="nav_about">درباره</a></li>
    <li><a href="#work" data-i18n="nav_work">نمونه‌کار</a></li>
    <li><a href="blog.html" data-i18n="nav_blog">بلاگ</a></li>
    <li><a href="#assistant" data-i18n="nav_assistant">مشاور هوشمند</a></li>
    <li id="navAuthLi"><a href="register.html" id="navAuthBtn" data-i18n="nav_register">ورود / ثبت‌نام</a></li>
    <li><button id="langToggle" class="lang-toggle">EN</button></li>
  </ul>
</header>
<div class="nav-backdrop" id="navBackdrop"></div>

<!-- ============== HERO (compact, split) ============== -->
<section class="hero-stage" id="home">
  <div class="hero-sticky">
    <div class="hero-inner">
      <div class="hero-badges-row">
        <span class="hero-eyebrow-badge" id="heroBadgeText"><?php echo sc('hero_eyebrow_badge','✓ اینماد تأیید‌شده'); ?></span>
        <span id="heroExtraBadges"><?php echo scExtraBadges(); ?></span>
      </div>
      <div class="tag" data-i18n="hero_tag" id="heroTagText"><?php echo sc('hero_tag','۲۰۲۵ — Portfolio · Content · AI Art'); ?></div>
      <h1>
        <span id="heroTitle"><?php echo scHeroTitle('هوش مصنوعی با <em>مهدی محرابی</em>'); ?></span>
      </h1>
      <p class="lead" id="heroLead">
        تولید محتوا و هوش مصنوعی در شیراز. ویدیوگرافی، تبلیغات ویدیویی، فیلم‌برداری
        و ساخت فیلم و محتوا با هوش مصنوعی — برای برندها و کسب‌وکارهای شیراز و سراسر ایران.
      </p>
      <div class="hero-actions">
        <a href="#courses" class="btn-primary">مشاهده دوره‌ها</a>
        <a href="#work" class="btn-secondary">دیدن نمونه‌کارها</a>
      </div>
      <div class="hero-trust-row">
        <div class="hero-trust-item"><span class="n" id="heroStatYears"><?php echo sc('stat_years','۷'); ?></span><span class="l">سال تجربه</span></div>
        <div class="hero-trust-item"><span class="n" id="heroStatProjects"><?php echo sc('stat_projects','+۱۲۰'); ?></span><span class="l">پروژه</span></div>
        <div class="hero-trust-item"><span class="n" id="heroStatBrands"><?php echo sc('stat_brands','+۶۰'); ?></span><span class="l">برند</span></div>
      </div>
    </div>
    <div class="hero-media">
      <img src="assets/portrait.jpg" alt="مهدی محرابی" id="heroPoster"/>
      <video id="heroFrameVideo" src="assets/bg.mp4" poster="assets/portrait.jpg" muted playsinline webkit-playsinline loop autoplay preload="auto" aria-hidden="true"></video>
    </div>
  </div>
</section>

<!-- ============== CONTENT WRAPPER ============== -->
<div class="content">

<!-- ============== COURSES (بلافاصله بعد از هیرو) ============== -->
<section class="section" id="courses">
  <div class="reveal">
    <div class="eyebrow" data-i18n="courses_eyebrow" id="coursesEyebrowText"><?php echo sc('courses_eyebrow_custom','۰۱ — دوره‌های آموزشی'); ?></div>
    <h2 data-i18n="courses_h2" id="coursesTitleText"><?php echo sc('courses_title','یاد بگیر، خودت <em>بساز</em>.'); ?></h2>
    <p class="section-lead" data-i18n="courses_lead">دوره‌های آموزش تخصصی تولید محتوا با هوش مصنوعی و تولید محتوای پیشرفته با موبایل.</p>
  </div>
  <div id="featuredCourseSlot"></div>
  <div class="courses-grid" id="coursesGrid">
    <div class="work-loading">در حال بارگذاری دوره‌ها…</div>
  </div>

  <!-- سؤالات متداول — بخشی از دوره‌ها -->
  <div class="faq-inline" id="faqSection" style="display:none">
    <h3 id="faqTitleText"><?php echo sc('faq_title','سؤالات <em>متداول</em>'); ?></h3>
    <div class="faq-list" id="faqList"></div>
  </div>
</section>

<!-- ============== ABOUT ============== -->
<section class="section" id="about">
  <div class="reveal">
    <div class="eyebrow" data-i18n="about_eyebrow" id="aboutEyebrowText"><?php echo sc('about_eyebrow_custom','۰۲ — درباره'); ?></div>
    <h2 data-i18n="about_h2" id="aboutTitleText"><?php echo sc('about_title','هنرمندی که با <em>الگوریتم</em> گفت‌وگو می‌کند.'); ?></h2>
  </div>
  <div class="about-grid">
    <div class="about-portrait reveal">
      <img src="assets/portrait.jpg" alt="مهدی محرابی" />
      <div class="meta">Portrait · 2025</div>
    </div>
    <div class="about-text reveal">
      <p class="first" id="aboutP1"><?php echo sc('about_p1', 'مهدی محرابی هستم؛ تولیدکننده‌ی محتوا و هنرمند هوش مصنوعی در شیراز. کارم در نقطه‌ی تلاقی روایت، طراحی و یادگیری ماشین شکل می‌گیرد — از ویدیوگرافی و فیلم‌برداری حرفه‌ای تا ساخت محتوا و فیلم با هوش مصنوعی.'); ?></p>
      <p id="aboutP2"><?php echo sc('about_p2', 'برای برندها و کسب‌وکارهای شیراز و سراسر ایران، تبلیغات ویدیویی، محتوای شبکه‌های اجتماعی و هویت بصری منحصربه‌فرد تولید می‌کنم. همچنین در دوره‌های آموزشی، تجربه‌ام در تولید محتوا با هوش مصنوعی و تصویربرداری با موبایل را منتقل می‌کنم. سفارش‌ها را از هر شهر و کشوری می‌پذیرم و تحویل می‌دهم.'); ?></p>
      <p data-i18n="about_p3">
        فلسفه‌ام ساده است — تکنولوژی باید در خدمت احساس باشد، نه برعکس.
      </p>

      <div class="stats">
        <div class="stat"><div class="n" id="statProjects"><?php echo sc('stat_projects','+۱۲۰'); ?></div><div class="l" data-i18n="stat_projects_label">پروژه</div></div>
        <div class="stat"><div class="n" id="statBrands"><?php echo sc('stat_brands','+۶۰'); ?></div><div class="l" data-i18n="stat_brands_label">برند</div></div>
        <div class="stat"><div class="n" id="statYears"><?php echo sc('stat_years','۷'); ?></div><div class="l" data-i18n="stat_years_label">سال تجربه</div></div>
      </div>
    </div>
  </div>
</section>

<!-- ============== TESTIMONIALS ============== -->
<section class="section" id="testimonials">
  <div class="reveal">
    <div class="eyebrow" id="testiEyebrowText"><?php echo sc('testimonials_eyebrow','۰۳ — تجربه دانشجویان'); ?></div>
    <h2 id="testiTitleText"><?php echo sc('testimonials_title','اونا چی <em>می‌گن</em>؟'); ?></h2>
  </div>
  <div class="testimonials-grid" id="testimonialsGrid">
    <div class="work-loading">در حال بارگذاری…</div>
  </div>
</section>

<!-- ============== PORTFOLIO / WORK ============== -->
<section class="section" id="work">
  <div class="reveal">
    <div class="eyebrow" data-i18n="work_eyebrow" id="workEyebrowText"><?php echo sc('work_eyebrow_custom','۰۴ — نمونه‌کار'); ?></div>
    <h2 data-i18n="work_h2" id="workTitleText"><?php echo sc('work_title','نمونه <em>کارها</em>.'); ?></h2>
  </div>
  <div class="work-grid" id="workGrid">
    <div class="work-loading" data-i18n="loading_works">در حال بارگذاری نمونه‌کارها…</div>
  </div>
</section>
</div><!-- /.content -->

<!-- ============== AI ASSISTANT ============== -->
<section class="assistant" id="assistant">
  <div class="assistant-inner">
    <div class="reveal">
      <div class="eyebrow" data-i18n="assistant_eyebrow" id="assistantEyebrowText"><?php echo sc('assistant_eyebrow_custom','۰۵ — مشاور هوشمند'); ?></div>
      <h2 data-i18n="assistant_h2" id="assistantTitleText"><?php echo sc('assistant_title','ایده بگیر، <em>سناریو</em> بساز.'); ?></h2>
      <p data-i18n="assistant_p">
        دستیار هوشمند استودیو فقط جواب سؤال نمی‌ده — برات سناریو می‌نویسه، ایده می‌ده،
        و توی ساختن محتوا همراهیت می‌کنه. چه مشتری باشی چه هنرجو، هر روز می‌تونی بیای
        و روی ایده‌هات کار کنی. برای قیمت و سفارش هم راهنماییت می‌کنه که با مهدی تماس بگیری.
      </p>
    </div>

    <div class="chat-card reveal">
      <div class="chat-head">
        <div class="chat-dot"></div>
        <div class="t" data-i18n="chat_title">دستیار استودیو</div>
        <div class="s" data-i18n="chat_status">آنلاین</div>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="msg bot" id="chatWelcome">
          سلام 👋 من دستیار استودیوی مهدی محرابی‌ام. می‌تونم برات سناریو و ایده بنویسم،
          توی پروژه‌ات مشورت بدم، یا راهنمایی‌ات کنم. موضوع یا ایده‌ات رو بگو تا شروع کنیم.
        </div>
      </div>
      <div class="chat-input">
        <input id="chatInput" type="text" placeholder="پیامت رو بنویس..." data-i18n-ph="chat_placeholder" autocomplete="off" />
        <button id="chatSend" data-i18n="chat_send">ارسال</button>
      </div>
    </div>
  </div>
</section>

<section class="contact" id="contact">
  <div class="contact-inner reveal">
    <div class="eyebrow" id="contactEyebrowText"><?php echo sc('contact_eyebrow','۰۶ — تماس'); ?></div>
    <h2 id="contactTitleText"><?php echo sc('contact_title','بیا <em>باهم</em> کار کنیم.'); ?></h2>
    <p class="lead" id="contactLead"><?php echo sc('contact_lead','برای خرید دوره، سفارش ویدیو یا هر سوالی مستقیم پیام بده. معمولاً ظرف چند ساعت پاسخ می‌دم.'); ?></p>
    <div class="contact-cards">
      <a class="contact-card" id="waLink" href="https://wa.me/989170073010" target="_blank">
        <div class="cc-icon">💬</div>
        <div class="cc-label">واتساپ</div>
        <div class="cc-val" id="contactPhone">۰۹۱۷۰۰۷۳۰۱۰</div>
      </a>
      <a class="contact-card" id="tgLink" href="https://t.me/+989170073010" target="_blank">
        <div class="cc-icon">✈️</div>
        <div class="cc-label">تلگرام</div>
        <div class="cc-val">@Filmehdy</div>
      </a>
      <a class="contact-card" id="igLink" href="https://instagram.com/Filmehdy" target="_blank">
        <div class="cc-icon">📸</div>
        <div class="cc-label">اینستاگرام</div>
        <div class="cc-val" id="contactInstagram">@Filmehdy</div>
      </a>
      <a class="contact-card" id="emailCard" href="mailto:mehdimehrabionline@gmail.com">
        <div class="cc-icon">✉️</div>
        <div class="cc-label">ایمیل</div>
        <div class="cc-val" id="contactEmail">mehdimehrabionline@gmail.com</div>
      </a>
    </div>
    <div class="contact-cta-row">
      <a href="register.html" class="contact-cta-btn" id="contactCtaText"><?php echo sc('contact_cta_text','ثبت‌نام و خرید دوره ←'); ?></a>
      <span class="contact-location" id="contactLocationText"><?php echo sc('contact_location','📍 شیراز، ایران'); ?></span>
    </div>
  </div>
</section>

<!-- ============== FOOTER ============== -->
<footer>
  <div class="foot-inner">
    <div class="big">Let's <em>create.</em></div>
    <div class="foot-meta">
      <div>mehdimehrabionline@gmail.com</div>
      <div dir="ltr" style="margin-top:6px;text-align:right">۰۹۱۷۰۰۷۳۰۱۰</div>
      <div style="margin-top:8px">
        <a href="https://instagram.com/Filmehdy" target="_blank" rel="noopener">Instagram</a><a href="https://wa.me/989170073010" target="_blank" rel="noopener">WhatsApp</a><a href="https://t.me/+989170073010" target="_blank" rel="noopener">Telegram</a>
      </div>
    </div>
  </div>
  <div class="pwa-install-wrap">
    <button id="pwaInstall" onclick="installPWA()" style="display:none">📱 نصب اپلیکیشن روی گوشی</button>
  </div>
  <div class="enamad-wrap">
    <a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=7437957&Code=jLmdD0lCLV7eeJKo1o59E3HJZKipaqOj'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=7437957&Code=jLmdD0lCLV7eeJKo1o59E3HJZKipaqOj' alt='نماد اعتماد الکترونیکی' style='cursor:pointer' code='jLmdD0lCLV7eeJKo1o59E3HJZKipaqOj'></a>
  </div>
  <div class="foot-bot">
    <div>© ۲۰۲۵ MEHDI MEHRABI STUDIO</div>
    <div data-i18n="footer_credit">طراحی شده با عشق و کد</div>
  </div>
</footer>

<script>
/* ============== Internationalization (FA / EN) ============== */
const I18N = {
  fa: {
    nav_home:'خانه', nav_about:'درباره', nav_work:'نمونه‌کار', 
    nav_courses:'دوره‌ها', nav_blog:'بلاگ', nav_assistant:'مشاور هوشمند', nav_register:'ورود / ثبت‌نام',
    hero_tag:'۲۰۲۵ — Portfolio · Content · AI Art',
    about_eyebrow:'۰۲ — درباره',
    about_h2:'هنرمندی که با <em>الگوریتم</em> گفت‌وگو می‌کند.',
    about_p3:'فلسفه‌ام ساده است — تکنولوژی باید در خدمت احساس باشد، نه برعکس.',
    stat_projects_label:'پروژه', stat_brands_label:'برند', stat_years_label:'سال تجربه',
    work_eyebrow:'۰۴ — نمونه‌کار', work_h2:'نمونه <em>کارها</em>.',
    loading_works:'در حال بارگذاری نمونه‌کارها…',
    ai_eyebrow:'۰۳ — آثار هوش مصنوعی', ai_h2:'وقتی ماشین <em>رؤیا</em> می‌بیند.',
    loading_ai:'در حال بارگذاری آثار…',
    courses_eyebrow:'۰۱ — دوره‌های آموزشی', courses_h2:'یاد بگیر، خودت <em>بساز</em>.',
    courses_lead:'دوره‌های آموزش تخصصی تولید محتوا با هوش مصنوعی و تولید محتوای پیشرفته با موبایل.',
    course_tag:'آموزش', course_cta:'ثبت‌نام و مشاوره ←',
    course1_title:'دوره‌ی جامع تصویربرداری و تدوین با موبایل',
    course1_desc:'از صفر تا تولید ویدیوی حرفه‌ای فقط با موبایل: اصول تصویربرداری، نورپردازی، ترکیب‌بندی و تدوین. مناسب کسانی که می‌خواهند بدون تجهیزات گران، محتوای باکیفیت بسازند.',
    course2_title:'دوره‌ی جامع تولید محتوا با هوش مصنوعی',
    course2_desc:'تسلط بر ابزارهای هوش مصنوعی برای ساخت تصویر، ویدیو و محتوای خلاقانه. از ایده تا خروجی نهایی، یاد می‌گیری چطور با AI سریع‌تر و حرفه‌ای‌تر محتوا تولید کنی.',
    assistant_eyebrow:'۰۵ — مشاور هوشمند', assistant_h2:'ایده بگیر، <em>سناریو</em> بساز.',
    assistant_p:'دستیار هوشمند استودیو فقط جواب سؤال نمی‌ده — برات سناریو می‌نویسه، ایده می‌ده، و توی ساختن محتوا همراهیت می‌کنه. چه مشتری باشی چه هنرجو، هر روز می‌تونی بیای و روی ایده‌هات کار کنی.',
    chat_title:'دستیار استودیو', chat_status:'آنلاین', chat_send:'ارسال',
    chat_placeholder:'پیامت رو بنویس...',
    chat_welcome:'سلام 👋 من دستیار استودیوی مهدی محرابی‌ام. می‌تونم برات سناریو و ایده بنویسم، توی پروژه‌ات مشورت بدم، یا راهنمایی‌ات کنم. موضوع یا ایده‌ات رو بگو تا شروع کنیم.',
    contact_eyebrow:'۰۶ — تماس', contact_h2:'یک <em>پروژه</em> در ذهن داری؟',
    contact_lead:'فرم رو پر کن یا مستقیم پیام بده. معمولاً ظرف ۲۴ ساعت پاسخ می‌دم.',
    contact_phone_label:'تلفن / واتساپ / تلگرام', contact_location_label:'LOCATION', contact_location:'شیراز، ایران',
    social_whatsapp:'واتساپ', social_telegram:'تلگرام', social_instagram:'اینستاگرام',
    form_name:'نام و نام خانوادگی', form_email:'ایمیل', form_phone:'شماره تماس',
    form_service:'نوع خدمت', form_message:'شرح پروژه', form_submit:'ارسال سفارش',
    form_ok:'پیامت با موفقیت ثبت شد. به‌زودی تماس می‌گیریم.',
    service_course:'ثبت‌نام در دوره آموزشی', service_video:'سفارش ساخت ویدیو',
    footer_credit:'طراحی شده با عشق و کد',
    lang_btn:'EN'
  },
  en: {
    nav_home:'Home', nav_about:'About', nav_work:'Work', 
    nav_courses:'Courses', nav_blog:'Blog', nav_assistant:'AI Assistant', nav_register:'Login / Sign up',
    hero_tag:'2025 — Portfolio · Content · AI Art',
    about_eyebrow:'02 — About',
    about_h2:'An artist in <em>dialogue</em> with the algorithm.',
    about_p3:'My philosophy is simple — technology should serve emotion, not the other way around.',
    stat_projects_label:'Projects', stat_brands_label:'Brands', stat_years_label:'Years',
    work_eyebrow:'04 — Work', work_h2:'<em>Content</em> &amp; visual identity.',
    loading_works:'Loading work…',
    ai_eyebrow:'03 — AI Works', ai_h2:'When the machine <em>dreams</em>.',
    loading_ai:'Loading works…',
    courses_eyebrow:'01 — Courses', courses_h2:'Learn, and <em>create</em> it yourself.',
    courses_lead:'Specialized courses in AI content creation and advanced mobile content production.',
    course_tag:'Course', course_cta:'Enroll &amp; consult →',
    course1_title:'Complete Mobile Videography & Editing Course',
    course1_desc:'From zero to professional video using only your phone: shooting fundamentals, lighting, composition and editing. Ideal for creating high-quality content without expensive gear.',
    course2_title:'Complete AI Content Creation Course',
    course2_desc:'Master AI tools to create images, video and creative content. From idea to final output, learn how to produce content faster and more professionally with AI.',
    assistant_eyebrow:'05 — AI Assistant', assistant_h2:'Get ideas, build a <em>script</em>.',
    assistant_p:'The studio assistant does more than answer questions — it writes scripts, suggests ideas, and helps you build content. Whether you are a client or a student, you can come back daily to work on your ideas.',
    chat_title:'Studio Assistant', chat_status:'Online', chat_send:'Send',
    chat_placeholder:'Type your message...',
    chat_welcome:'Hi 👋 I am Mehdi Mehrabi studio\u2019s assistant. I can write scripts and ideas for you, advise on your project, or guide you. Tell me your topic or idea to get started.',
    contact_eyebrow:'06 — Contact', contact_h2:'Have a <em>project</em> in mind?',
    contact_lead:'Fill out the form or message directly. I usually reply within 24 hours.',
    contact_phone_label:'PHONE / WHATSAPP / TELEGRAM', contact_location_label:'LOCATION', contact_location:'Shiraz, Iran',
    social_whatsapp:'WhatsApp', social_telegram:'Telegram', social_instagram:'Instagram',
    form_name:'Full name', form_email:'Email', form_phone:'Phone number',
    form_service:'Service type', form_message:'Project description', form_submit:'Send order',
    form_ok:'Your message was sent successfully. We will contact you soon.',
    service_course:'Enroll in a course', service_video:'Order video production',
    footer_credit:'Designed with love and code',
    lang_btn:'فا'
  }
};

let currentLang = localStorage.getItem('mm_lang') || 'fa';

function applyLang(lang){
  currentLang = lang;
  localStorage.setItem('mm_lang', lang);
  const dict = I18N[lang];
  // جهت و زبان صفحه
  document.documentElement.lang = lang;
  document.documentElement.dir = (lang === 'fa') ? 'rtl' : 'ltr';
  // متن‌ها
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const k = el.getAttribute('data-i18n');
    if(dict[k] != null) el.innerHTML = dict[k];
  });
  // placeholderها
  document.querySelectorAll('[data-i18n-ph]').forEach(el => {
    const k = el.getAttribute('data-i18n-ph');
    if(dict[k] != null) el.placeholder = dict[k];
  });
  // دکمه‌ی زبان
  const lt = document.getElementById('langToggle');
  if(lt) lt.textContent = dict.lang_btn;
  // پیام خوش‌آمد دستیار (اگر هنوز گفتگو شروع نشده)
  const welcome = document.getElementById('chatWelcome');
  if(welcome) welcome.textContent = dict.chat_welcome;
  // محتوای داینامیک را با زبان جدید دوباره بارگذاری کن
  if(window.__dataLoaded){ loadSiteContent(); loadProjects(); loadCourses(); }
}

/* ── Work filter ── */
(function(){
  const lt = document.getElementById('langToggle');
  if(lt) lt.addEventListener('click', () => applyLang(currentLang === 'fa' ? 'en' : 'fa'));
})();

/* ============== ویدیوی هیرو — پخش فوری، بدون تأخیر مصنوعی ============== */
(function(){
  const video = document.getElementById('heroFrameVideo');
  if (!video) return;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reducedMotion) return; // احترام به تنظیمات کاربر — عکس ثابت می‌مونه (poster)

  function tryPlay(){
    const p = video.play();
    if (p && p.then) p.catch(()=>{ /* موبایل ممکنه اجازه ندی، با اولین لمس دوباره امتحان می‌کنیم */ });
  }
  // اگه واقعاً فایل ویدیو لود نشد (خطای شبکه)، برگرد به عکس ثابت
  video.addEventListener('error', () => {
    const wrap = video.closest('.hero-media');
    if (wrap) wrap.classList.add('video-failed');
  }, { once:true });

  tryPlay();
  window.addEventListener('load', tryPlay);
  // اولین لمس/اسکرول کاربر روی موبایل — بعضی مرورگرها بدون این پخش خودکار رو رد می‌کنن
  ['touchstart','scroll','click'].forEach(ev => window.addEventListener(ev, tryPlay, { once:true, passive:true }));
})();

/* ============== ویدیوی پس‌زمینه کم‌رنگ — اسکرول‌محور، فقط تا انتهای دو بخش اول ============== */
(function(){
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const bgVideo = document.getElementById('bgScrollVideo');
  const bgVeil = document.getElementById('bgVideoVeil');
  if (!bgVideo || !bgVeil || reducedMotion) { if(bgVeil) bgVeil.style.opacity='1'; return; }

  let ready=false, duration=10, targetTime=0, displayTime=0;
  // دانلود ویدیوی پس‌زمینه بعد از اینکه بخش‌های اصلی صفحه کامل لود شدن شروع می‌شه (نه هم‌زمان با اون‌ها)
  const startBgVideo = () => {
    if (bgVideo.getAttribute('src')) return;
    if (navigator.connection && navigator.connection.saveData) { bgVeil.style.opacity = '1'; return; }
    bgVideo.src = bgVideo.dataset.src;
    bgVideo.preload = 'auto';
    try { bgVideo.load(); } catch(e) {}
  };
  if (document.readyState === 'complete') setTimeout(startBgVideo, 1500);
  else window.addEventListener('load', () => setTimeout(startBgVideo, 1500));
  bgVideo.addEventListener('loadedmetadata', () => {
    if (isFinite(bgVideo.duration) && bgVideo.duration > 0) duration = bgVideo.duration;
    ready = true;
    bgVideo.pause();
    const p = bgVideo.play();
    if (p && p.then) p.then(() => bgVideo.pause()).catch(()=>{});
  });

  function getStageHeight(){
    // اسکرول فقط تا انتهای بخش دوره‌ها (هیرو + بخش اول) حساب می‌شه
    const coursesEl = document.getElementById('courses');
    if (!coursesEl) return window.innerHeight * 2;
    const rect = coursesEl.getBoundingClientRect();
    const bottomAbs = rect.bottom + window.scrollY;
    return Math.max(200, bottomAbs - window.innerHeight);
  }

  function computeBgTargets(){
    const stageH = getStageHeight();
    const p = Math.min(1, Math.max(0, window.scrollY / stageH));
    targetTime = p * duration;
    // فقط بعد از عبور کامل از هیرو + دوره‌ها محو می‌شه (نه زودتر)
    const fadeStart = 0.96;
    const vp = Math.min(1, Math.max(0, (p - fadeStart) / (1 - fadeStart)));
    bgVeil.style.opacity = vp.toFixed(3);
  }

  function tick(){
    if (ready){
      displayTime += (targetTime - displayTime) * 0.18;
      if (Math.abs(targetTime - displayTime) < 0.01) displayTime = targetTime;
      if (Math.abs(bgVideo.currentTime - displayTime) > 0.02){
        try { if (bgVideo.fastSeek) bgVideo.fastSeek(displayTime); else bgVideo.currentTime = displayTime; } catch(e){}
      }
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);

  window.addEventListener('scroll', computeBgTargets, {passive:true});
  window.addEventListener('resize', computeBgTargets);
  computeBgTargets();
})();

/* ============== Mobile menu ============== */
const menuBtn = document.getElementById('menuBtn');

const navMenu = document.getElementById('navMenu');
const navEl = document.getElementById('nav');
const navInner = document.getElementById('navInner');
const navBackdrop = document.getElementById('navBackdrop');
menuBtn.addEventListener('click', () => {
  navMenu.classList.toggle('open');
  navEl.classList.toggle('menu-open');
  if(navBackdrop) navBackdrop.classList.toggle('show');
  menuBtn.textContent = navMenu.classList.contains('open') ? '×' : '≡';
});
navMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
  navMenu.classList.remove('open'); navEl.classList.remove('menu-open');
  if(navBackdrop) navBackdrop.classList.remove('show');
  menuBtn.textContent = '≡';
}));
if(navBackdrop) navBackdrop.addEventListener('click', () => {
  navMenu.classList.remove('open'); navEl.classList.remove('menu-open');
  navBackdrop.classList.remove('show');
  menuBtn.textContent = '≡';
});

/* ── نوار بالا: با اسکرول به پایین مخفی، با اسکرول به بالا دوباره نمایان ──
   فقط روی nav-inner اعمال می‌شه (نه کل header) چون ul از عمد بیرون این عنصره */
(function(){
  if (!navInner) return;
  let lastY = window.scrollY;
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const y = window.scrollY;
      const menuOpen = navEl.classList.contains('menu-open');
      if (!menuOpen) {
        const hide = (y > lastY && y > 120);
        navInner.classList.toggle('nav-hidden', hide);
        navEl.classList.toggle('nav-hidden', hide);
      }
      lastY = y;
      ticking = false;
    });
  }, { passive:true });
})();

/* ============== Reveal on scroll ============== */
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); }});
}, {threshold:.12, rootMargin:'0px 0px -60px 0px'});
document.querySelectorAll('.reveal').forEach(el => io.observe(el));

/* ============== AI Chat (Gemini via Supabase Edge Function) ============== */
const chatBody = document.getElementById('chatBody');
const chatInput = document.getElementById('chatInput');
const chatSend = document.getElementById('chatSend');

const PHONE_DISPLAY = '۰۹۱۷۰۰۷۳۰۱۰';
const chatHistory = [];   // {role:'user'|'model', text}

function addMsg(text, who='bot'){
  const d = document.createElement('div');
  d.className = 'msg ' + who;
  d.textContent = text;
  chatBody.appendChild(d);
  chatBody.scrollTop = chatBody.scrollHeight;
  return d;
}
function addTyping(){
  const d = document.createElement('div');
  d.className = 'msg typing';
  d.innerHTML = '<span></span><span></span><span></span>';
  chatBody.appendChild(d);
  chatBody.scrollTop = chatBody.scrollHeight;
  return d;
}

/* پاسخ پشتیبان وقتی دستیار هوش مصنوعی در دسترس نیست */
const KB = [
  {k:['قیمت','هزینه','نرخ','چقدر','تعرفه','مبلغ'], a:'برای قیمت‌گذاری دقیق، اول باید سناریو و جزئیات پروژه مشخص بشه. بهترین راه اینه که با مهدی تماس بگیری یا یه جلسه بذاری تا با هم پروژه رو دقیق بررسی کنیم و قیمت نهایی بدیم. شماره‌ی تماس: '+PHONE_DISPLAY+' (تلفن، واتساپ و تلگرام).'},
  {k:['تماس','شماره','تلفن','زنگ','واتساپ','تلگرام','ارتباط'], a:'می‌تونی مستقیم با مهدی صحبت کنی: '+PHONE_DISPLAY+' — همین شماره روی واتساپ و تلگرام هم فعاله. یا ایمیل: mehdimehrabionline@gmail.com'},
  {k:['سناریو','ایده','کانسپت','طرح'], a:'با کمال میل! توضیح بده موضوع، مخاطب و حال‌وهوای پروژه‌ات چیه تا یه سناریو و ایده‌ی اولیه برات پیشنهاد بدم. برای اجرای حرفه‌ای هم می‌تونی با مهدی تماس بگیری: '+PHONE_DISPLAY},
  {k:['خدمات','چی کار','چه کاری','سرویس'], a:'خدمات استودیو: تولید محتوای ویدیویی، فیلم‌برداری و تدوین، تبلیغات ویدیویی، ساخت محتوا و فیلم با هوش مصنوعی، و دوره‌های آموزشی. درباره‌ی کدوم می‌خوای بدونی؟'},
  {k:['دوره','آموزش','یاد','کلاس','هنرجو'], a:'دو دوره داریم: «تصویربرداری و تدوین با موبایل» و «تولید محتوا با هوش مصنوعی». برای ثبت‌نام و مشاوره با مهدی تماس بگیر: '+PHONE_DISPLAY},
  {k:['سلام','درود','هی','هلو','وقت بخیر'], a:'سلام! خوش اومدی 👋 من دستیار استودیوی مهدی محرابی‌ام. می‌تونم برات ایده و سناریو بنویسم، درباره‌ی خدمات راهنمایی‌ات کنم، یا کمکت کنم پروژه‌ات رو شکل بدی. چی تو ذهنته؟'},
  {k:['ممنون','مرسی','تشکر','دمت'], a:'خواهش می‌کنم 🙏 هر وقت ایده یا سؤالی داشتی، همین‌جام.'},
];
const FALLBACK = 'برای اینکه دقیق کمکت کنم، یه‌کم بیشتر درباره‌ی پروژه یا ایده‌ات بگو. و هر وقت خواستی، می‌تونی مستقیم با مهدی صحبت کنی: '+PHONE_DISPLAY+' (تلفن/واتساپ/تلگرام).';
const FALLBACK_EN = 'To help you precisely, tell me a bit more about your project or idea. Whenever you like, you can talk to Mehdi directly: '+PHONE_DISPLAY+' (phone/WhatsApp/Telegram).';

function localReply(text){
  if(currentLang === 'en') return FALLBACK_EN;   // پشتیبان انگلیسی ساده
  const t = text.toLowerCase();
  for(const r of KB){ if(r.k.some(k => t.includes(k.toLowerCase()))) return r.a; }
  return FALLBACK;
}

async function getReply(text){
  // تلاش برای استفاده از دستیار هوش مصنوعی (Edge Function به نام chat)
  try{
    const { data, error } = await sb.functions.invoke('chat', {
      body: { messages: chatHistory.slice(-12), lang: currentLang }
    });
    if(error) throw error;
    if(data && data.reply) return data.reply;
    throw new Error('no reply');
  }catch(e){
    console.warn('AI assistant unavailable, using fallback:', e.message);
    return localReply(text);
  }
}

async function sendChat(){
  const v = chatInput.value.trim();
  if(!v) return;
  addMsg(v,'user');
  chatHistory.push({role:'user', text:v});
  chatInput.value=''; chatSend.disabled=true;
  const typ = addTyping();
  const reply = await getReply(v);
  typ.remove();
  addMsg(reply,'bot');
  chatHistory.push({role:'model', text:reply});
  chatSend.disabled=false;
  chatInput.focus();
}

/* ── لود تنبل iframeها ── */
const frameObs = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const f = e.target;
      if (f.dataset.src && !f.src) f.src = f.dataset.src;
      frameObs.unobserve(f);
    }
  });
}, { rootMargin: '1200px' });

function observeFrames(){
  document.querySelectorAll('iframe.lazy-frame:not([src])').forEach(f => frameObs.observe(f));
}

/* ── Lightbox ── */
function aparatHash(url){
  // فرمت‌های مختلف آپارات:
  // aparat.com/v/ABC123
  // aparat.com/v/ABC123/...
  // aparat.com/ABC123
  const m = url.match(/aparat\.com\/v\/([a-zA-Z0-9]+)/i)
           || url.match(/aparat\.com\/([a-zA-Z0-9]{5,})/i);
  return m ? m[1] : null;
}
function aparatEmbed(url){
  const h = aparatHash(url);
  return h ? `https://www.aparat.com/video/video/embed/videohash/${h}/vt/frame` : null;
}
function aparatThumb(url){
  const h = aparatHash(url);
  return h ? `https://static.aparat.com/public/user-data/oid/1/${h}/q/s.jpg` : null;
}
function youtubeEmbed(url){
  const m=url.match(/[?&]v=([a-zA-Z0-9_-]+)/)||url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
  return m?`https://www.youtube.com/embed/${m[1]}?autoplay=1`:null;
}
function openLb(videoUrl){
  const lb=document.getElementById('videoLb');
  const frame=document.getElementById('lbFrame');
  const notice=document.getElementById('lbNotice');
  let embed=null, type='';
  if(videoUrl.includes('aparat.com')){ embed=aparatEmbed(videoUrl); type='aparat'; }  else if(videoUrl.includes('youtube')||videoUrl.includes('youtu.be')){ embed=youtubeEmbed(videoUrl); type='youtube'; }
  if(!embed){ window.open(videoUrl,'_blank'); return; }

  // لایت‌باکس فوری باز می‌شه، ویدیو در پس‌زمینه لود می‌شه
  if(type==='aparat'){
    notice.className='lb-notice off';
    notice.textContent='🔵 برای دیدن این ویدیو فیلترشکن خود را خاموش کنید.';
    embed += (embed.includes('?') ? '&' : '?') + 'autoplay=true';
  } else {
    notice.className='lb-notice on';
    notice.textContent='🔴 برای دیدن این ویدیو فیلترشکن خود را روشن کنید.';
    embed += (embed.includes('?') ? '&' : '?') + 'autoplay=1';
  }
  const ldr=document.getElementById('lbLoading');
  if(ldr) ldr.style.display='flex';
  lb.classList.add('open');
  document.body.style.overflow='hidden';
  frame.src=embed;
}
function closeLb(){
  document.getElementById('videoLb').classList.remove('open');
  document.getElementById('lbFrame').src='';
  document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeLb(); });

chatSend.addEventListener('click', sendChat);
chatInput.addEventListener('keydown', e => { if(e.key==='Enter') sendChat(); });

/* ============== Load dynamic content from Supabase ============== */
function setText(id, val){ const el = document.getElementById(id); if(el && val != null) el.textContent = val; }

/* برخی متن‌ها از پنل ادمین قابل ویرایشن — این تابع بعد از هر بار اعمال زبان یا لود محتوا، دوباره اجرا می‌شه */
function applyContentOverrides(){
  const map = window.__scMap;
  if(!map) return;
  if(map.nav_brand) { const el=document.getElementById('navBrandText'); if(el) el.innerHTML = map.nav_brand; }
  if(map.hero_eyebrow_badge) setText('heroBadgeText', map.hero_eyebrow_badge);
  if(map.hero_badges_extra){
    try{
      const extra = JSON.parse(map.hero_badges_extra);
      const wrap = document.getElementById('heroExtraBadges');
      if(wrap && Array.isArray(extra) && extra.length){
        wrap.innerHTML = extra.map(t=>`<span class="hero-eyebrow-badge">${t}</span>`).join('');
      }
    }catch(e){}
  }
  if(map.hero_tag) setText('heroTagText', map.hero_tag);
  if(map.courses_eyebrow_custom) setText('coursesEyebrowText', map.courses_eyebrow_custom);
  if(map.courses_title) setText('coursesTitleText', map.courses_title);
  if(map.about_eyebrow_custom) setText('aboutEyebrowText', map.about_eyebrow_custom);
  if(map.about_title) setText('aboutTitleText', map.about_title);
  if(map.work_eyebrow_custom) setText('workEyebrowText', map.work_eyebrow_custom);
  if(map.work_title) setText('workTitleText', map.work_title);
  if(map.assistant_eyebrow_custom) setText('assistantEyebrowText', map.assistant_eyebrow_custom);
  if(map.assistant_title) setText('assistantTitleText', map.assistant_title);
  if(map.testimonials_eyebrow) setText('testiEyebrowText', map.testimonials_eyebrow);
  if(map.testimonials_title) setText('testiTitleText', map.testimonials_title);
  if(map.faq_eyebrow) setText('faqEyebrowText', map.faq_eyebrow);
  if(map.faq_title) setText('faqTitleText', map.faq_title);
}

/* داده‌ها: اول از چیزی که سرور داخل خود صفحه گذاشته (فوری، بدون درخواست)، وگرنه از کش محلی سایت */
const __dataPromises = {};
function getData(type){
  if (window.__DATA && Array.isArray(window.__DATA[type])) return Promise.resolve(window.__DATA[type]);
  if (!__dataPromises[type]) {
    __dataPromises[type] = fetch('api/cache.php?type=' + type)
      .then(r => r.json())
      .then(d => { if (!Array.isArray(d)) throw new Error('bad data'); return d; });
    __dataPromises[type].catch(() => { delete __dataPromises[type]; });
  }
  return __dataPromises[type];
}

async function loadSiteContent(){
  try {
    let data;
    try {
      data = await getData('site_content');
      if (!Array.isArray(data)) throw new Error('fallback');
    } catch(e) {
      const res = await sb.from('site_content').select('key,value,value_en');
      if (res.error) throw res.error;
      data = res.data || [];
    }
    const map = {};
    (data||[]).forEach(r => {
      // اگر زبان انگلیسی است و ترجمه موجود است، از آن استفاده کن؛ وگرنه فارسی
      map[r.key] = (currentLang === 'en' && r.value_en) ? r.value_en : r.value;
    });
    window.__scMap = map; // کش سراسری برای اعمال دوباره بعد از تعویض زبان
    // hero
    if(map.hero_title){
      let t = map.hero_title.trim();
      // اگر متن دیتابیس تگ رنگ نداشت، آخرین کلمه را نارنجی کن
      if(!/<em>/i.test(t)){
        const parts = t.split(/\s+/);
        if(parts.length > 1){ const last = parts.pop(); t = parts.join(' ') + ' <em>' + last + '</em>'; }
        else { t = '<em>' + t + '</em>'; }
      }
      document.getElementById('heroTitle').innerHTML = t;
    }
    setText('heroLead', map.hero_lead);
    applyContentOverrides();
    // عنوان بخش‌های قابل ویرایش از پنل — فقط اگه واقعاً مقدار داشته باشن
    if(map.testimonials_eyebrow) setText('testiEyebrowText', map.testimonials_eyebrow);
    if(map.testimonials_title) setText('testiTitleText', map.testimonials_title);
    if(map.faq_eyebrow) setText('faqEyebrowText', map.faq_eyebrow);
    if(map.faq_title) setText('faqTitleText', map.faq_title);
    // about
    setText('aboutP1', map.about_p1);
    setText('aboutP2', map.about_p2);
    setText('statProjects', map.stat_projects);
    setText('statBrands', map.stat_brands);
    setText('statYears', map.stat_years);
    setText('heroStatProjects', map.stat_projects);
    setText('heroStatBrands', map.stat_brands);
    setText('heroStatYears', map.stat_years);
    // contact
    const ph = document.getElementById('contactPhone');
    if(ph && map.contact_phone){
      ph.textContent = map.contact_phone;
      const digits = map.contact_phone.replace(/[^0-9]/g,'');
      ph.href = 'tel:+98' + digits.replace(/^0/,'');
    }
    const em = document.getElementById('contactEmail');
    if(em && map.contact_email){ em.textContent = map.contact_email; em.href = 'mailto:' + map.contact_email; }
    const ig = document.getElementById('contactInstagram');
    if(ig && map.contact_instagram){
      const handle = map.contact_instagram.replace('@','');
      ig.textContent = handle + '@';
      ig.href = 'https://instagram.com/' + handle;
    }
    if(map.contact_lead) setText('contactLead', map.contact_lead);
    if(map.contact_eyebrow) setText('contactEyebrowText', map.contact_eyebrow);
    if(map.contact_title) setText('contactTitleText', map.contact_title);
    if(map.contact_cta_text) setText('contactCtaText', map.contact_cta_text);
    if(map.contact_location) setText('contactLocationText', map.contact_location);
  } catch(err){
    console.warn('site_content load failed:', err.message);
  }
}

// span patterns for the work grid (col widths) and ai mosaic (col/row spans)
const WORK_SPANS = ['w1','w2','w3','w4','w5','w6','w7'];
const AI_SPANS   = ['t1','t2','t3','t4','t5','t6','t7'];

function detectVideoType(url){
  if(!url) return null;
  if(url.includes('aparat.com')) return 'aparat';
  if(url.includes('youtube.com')||url.includes('youtu.be')) return 'youtube';
  if(url.match(/\.(mp4|webm|mov)(\?|$)/i)) return 'direct';
  return 'aparat'; // پیش‌فرض آپارات
}

function aparatEmbedUrl(url){
  // https://www.aparat.com/v/HASH → embed URL
  const m = url.match(/aparat\.com\/v\/([a-zA-Z0-9]+)/);
  if(m) return `https://www.aparat.com/video/video/embed/videohash/${m[1]}/vt/frame`;
  return url;
}

function youtubeEmbedUrl(url){
  let vid = '';
  const m1 = url.match(/[?&]v=([a-zA-Z0-9_-]+)/);
  const m2 = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
  if(m1) vid=m1[1]; else if(m2) vid=m2[1];
  return vid ? `https://www.youtube.com/embed/${vid}` : url;
}

function aparatThumbFrame(url){
  const h = aparatHash(url);
  return h ? `https://www.aparat.com/video/video/embed/videohash/${h}/vt/frame` : null;
}

function mediaEl(p){
  if(!p.media_url) return '';
  if(p.media_type === 'video'){
    const isAparat = p.media_url.includes('aparat.com');
    const isYoutube = p.media_url.includes('youtube.com') || p.media_url.includes('youtu.be');
    const badge = isAparat
      ? `<span class="vpn-badge aparat-badge">آپارات</span>`
      : isYoutube ? `<span class="vpn-badge youtube-badge">YouTube</span>` : '';

    let previewHtml = '';
    if(isAparat){
      const h = aparatHash(p.media_url);
      if(h){
        previewHtml = `<iframe class="lazy-frame" data-src="https://www.aparat.com/video/video/embed/videohash/${h}/vt/frame" style="position:absolute;inset:0;width:100%;height:100%;border:none;pointer-events:none;background:linear-gradient(135deg,#1a1410,#2a1f16)" tabindex="-1" loading="lazy"></iframe>`;
      }
    } else if(isYoutube){
      const m = p.media_url.match(/[?&]v=([a-zA-Z0-9_-]+)/)||p.media_url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
      if(m) previewHtml = `<img class="tile-media" src="https://img.youtube.com/vi/${m[1]}/mqdefault.jpg" alt="" loading="lazy"/>`;
    }

    return `${previewHtml}
      <div class="video-thumb-overlay">
        ${badge}
        <div class="play-btn-circle">▶</div>
      </div>
      <div class="scrim"></div>`;
  }
  return `<img class="tile-media" src="${p.media_url}" alt="${(p.title||'').replace(/"/g,'')}" loading="lazy"/><div class="scrim"></div>`;
}

async function loadProjects(){
  try {
    let data;
    try {
      data = await getData('projects');
      if (!Array.isArray(data)) throw new Error('fallback');
    } catch(e) {
      const res = await sb.from('projects').select('id,title,title_en,category,category_en,year,description,description_en,media_type,media_url,orientation,link,section,sort_order').order('sort_order',{ascending:true}).order('created_at',{ascending:false});
      data = res.data || [];
    }
    const EN = currentLang === 'en';
    const pick = (p, f) => (EN && p[f+'_en']) ? p[f+'_en'] : p[f];
    const wg = document.getElementById('workGrid');
    const list = data||[];
    if(!list.length){ wg.innerHTML='<div class="tile-empty-note">'+(EN?'No work added yet.':'هنوز نمونه‌کاری اضافه نشده.')+'</div>'; return; }
    function typeTag(p){
      const cat=(p.category||'').toLowerCase();
      if(p.section==='ai'||cat.includes('هوش مصنوعی')||cat.includes('ai')) return '<span class="work-type-tag ai">'+(EN?'AI':'هوش مصنوعی')+'</span>';
      if(cat.includes('ویدیو')||cat.includes('video')||p.media_type==='video') return '<span class="work-type-tag video">'+(EN?'Video':'ویدیوگرافی')+'</span>';
      return '<span class="work-type-tag content">'+(EN?'Content':'محتوا')+'</span>';
    }
    wg.innerHTML = list.map(p => {
      const title = pick(p,'title')||'';
      const cat = [pick(p,'category'), p.year].filter(Boolean).join(' · ');
      const desc = pick(p,'description')||'';
      const orient = p.orientation==='portrait' ? 'portrait' : 'landscape';
      const descHtml = desc ? `<div class="work-desc">${desc}</div>` : '';
      const inner = `<div class="ph">${mediaEl(p)}${typeTag(p)}<div class="label"><div class="t">${title}</div><div class="c">${cat}</div></div></div>${descHtml}`;
      if(p.media_type==='video' && p.media_url) return `<div class="work ${orient} reveal" onclick="openLb('${p.media_url}')" style="cursor:pointer">${inner}</div>`;
      return p.link ? `<a class="work ${orient} reveal" href="${p.link}" target="_blank" rel="noopener">${inner}</a>` : `<div class="work ${orient} reveal">${inner}</div>`;
    }).join('');
    document.querySelectorAll('.reveal:not(.in)').forEach(el => io.observe(el));
    observeFrames();
  } catch(err){ console.warn('projects load failed:', err.message); }
}

/* ============== Load courses ============== */
async function loadCourses(){
  try {
    let data;
    try {
      data = await getData('courses');
      if (!Array.isArray(data)) throw new Error('fallback');
    } catch(e) {
      const res = await sb.from('courses').select('id,title,title_en,tag,tag_en,description,description_en,price,price_en,level,mode,duration,capacity,start_date,cover_url,video_url,link,is_featured,sort_order,original_price_amount,discount_active,discount_label,discount_percent,slug').eq('is_active',true).order('sort_order',{ascending:true});
      data = res.data || [];
    }
    if(!data || data.length === 0) return;
    const EN = currentLang === 'en';
    const pick = (c, f) => (EN && c[f+'_en']) ? c[f+'_en'] : c[f];
    const grid = document.getElementById('coursesGrid');
    const slot = document.getElementById('featuredCourseSlot');
    if (slot) slot.innerHTML = '';
    grid.classList.add('courses-list');
    const fa = s => EN ? String(s) : faNum(s);

    // دوره‌های ویژه اول، بقیه به ترتیب تعیین‌شده در پنل
    const list = data.slice().sort((a,b) => (b.is_featured?1:0) - (a.is_featured?1:0));

    grid.innerHTML = list.map(c => {
      const courseLink = c.link && c.link.startsWith('http') ? c.link : (c.slug ? `/c/${c.slug}` : `course.html?id=${c.id}`);
      const hasDiscount = c.discount_active && c.original_price_amount;
      const title = pick(c,'title') || '';
      let ribbon = '';
      if (c.discount_active && (c.discount_label || c.discount_percent)) {
        ribbon = `🔥 ${c.discount_label || ''}${c.discount_percent ? ` ${fa(c.discount_percent)}${EN?'% OFF':'٪ تخفیف'}` : ''}`;
      } else if (c.is_featured) {
        ribbon = EN ? '🔥 Featured Course' : '🔥 دوره ویژه';
      }
      const metas = [
        c.level && `<span class="cmeta-item">📊 ${pick(c,'level')||c.level}</span>`,
        c.mode && `<span class="cmeta-item">📍 ${pick(c,'mode')||c.mode}</span>`,
        c.duration && `<span class="cmeta-item">⏱ ${fa(pick(c,'duration')||c.duration)}</span>`,
        c.capacity && `<span class="cmeta-item">👥 ${EN?'Capacity':'ظرفیت'}: ${fa(c.capacity)}</span>`,
        c.start_date && `<span class="cmeta-item">📅 ${EN?'Starts':'شروع'}: ${fa(c.start_date)}</span>`,
      ].filter(Boolean).join('');
      const media = c.cover_url
        ? `<img src="${c.cover_url}" alt="${title}" loading="lazy"/>`
        : `<div class="fc-ph">${title}</div>`;
      return `
      <a href="${courseLink}" class="featured-course reveal" style="text-decoration:none;color:inherit">
        ${media}
        <div class="fc-body">
          ${ribbon ? `<span class="ribbon">${ribbon}</span>` : (pick(c,'tag') ? `<span class="fc-tag">${pick(c,'tag')}</span>` : '')}
          <h3>${title}</h3>
          ${metas ? `<div class="course-metas fc-metas">${metas}</div>` : ''}
          <p>${pick(c,'description')||''}</p>
          <div class="price-row">
            ${hasDiscount ? `<span class="price-old">${Number(c.original_price_amount).toLocaleString(EN?'en-US':'fa-IR')}${EN?' Toman':' تومان'}</span>` : ''}
            ${c.price ? `<span class="price-new">${fa(pick(c,'price')||c.price)}</span>` : ''}
          </div>
          <span class="fc-cta">${EN?'View & Enroll →':'مشاهده و ثبت‌نام ←'}</span>
        </div>
      </a>`;
    }).join('');
    document.querySelectorAll('.reveal:not(.in)').forEach(el => io.observe(el));
  } catch(err){ console.warn('courses load skipped:', err.message); }
}

/* اعداد فارسی برای نمایش عمومی سایت */
function faNum(n){ return String(n).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]); }

/* ── کارت دوره ویژه (spotlight) ── */
function renderFeaturedSpotlight(data, EN){
  const slot = document.getElementById('featuredCourseSlot');
  if (!slot) return;
  const fc = data.find(c => c.is_featured);
  if (!fc) { slot.innerHTML=''; return; }

  const pick = f => (EN && fc[f+'_en']) ? fc[f+'_en'] : fc[f];
  const courseLink = fc.link && fc.link.startsWith('http') ? fc.link : (fc.slug ? `/c/${fc.slug}` : `course.html?id=${fc.id}`);
  const hasDiscount = fc.discount_active && fc.original_price_amount;
  const img = fc.cover_url || 'assets/portrait.jpg';

  slot.innerHTML = `
    <a href="${courseLink}" class="featured-course" style="text-decoration:none;color:inherit">
      <img src="${img}" alt="${pick('title')||''}" loading="lazy"/>
      <div class="fc-body">
        <span class="ribbon">🔥 ${fc.discount_label ? fc.discount_label : (EN?'Featured Course':'دوره ویژه')}</span>
        <h3>${pick('title')||''}</h3>
        <p>${pick('description')||''}</p>
        <div class="price-row">
          ${hasDiscount ? `<span class="price-old">${Number(fc.original_price_amount).toLocaleString(EN?'en-US':'fa-IR')}${EN?' Toman':' تومان'}</span>` : ''}
          ${fc.price ? `<span class="price-new">${pick('price')||fc.price}</span>` : ''}
        </div>
        <span class="fc-cta">${EN?'View & Enroll →':'مشاهده و ثبت‌نام ←'}</span>
      </div>
    </a>`;
}

/* initial */
applyLang(currentLang);
window.__dataLoaded = true;

function bootData(){
  // بدون هیچ انتظاری: داده‌ها از خود صفحه میان و به کتابخانه‌ی Supabase نیازی نیست
  loadSiteContent();
  loadProjects();
  loadCourses();
  loadPromo();
  loadFaqs();
}
// این اسکریپت انتهای صفحه‌ست و همه‌ی بخش‌ها از قبل وجود دارن
bootData();

/* ── نوار تبلیغاتی ── */
async function loadPromo(){
  if (sessionStorage.getItem('mm_promo_dismissed')) return;
  try {
    let data;
    try {
      data = await getData('site_content');
      if (!Array.isArray(data)) throw new Error('fb');
    } catch(e) {
      const res = await sb.from('site_content').select('key,value');
      data = res.data || [];
    }
    const map={}; data.forEach(r=>map[r.key]=r.value);
    if (map.promo_enabled !== 'true' || !map.promo_text) return;
    document.getElementById('promoBarText').textContent = map.promo_text;
    const linkEl = document.getElementById('promoBarLink');
    if (map.promo_link) {
      linkEl.href = map.promo_link;
      linkEl.textContent = map.promo_link_text || 'مشاهده ←';
      linkEl.style.display = '';
    } else { linkEl.style.display = 'none'; }
    document.getElementById('promoBar').style.display = 'block';
  } catch(e){}
}
function dismissPromo(){
  document.getElementById('promoBar').style.display = 'none';
  sessionStorage.setItem('mm_promo_dismissed', '1');
}

/* ── سؤالات متداول ── */
async function loadFaqs(){
  const wrap = document.getElementById('faqSection');
  if (!wrap) return;
  try {
    let data;
    try {
      data = await getData('faqs');
      if (!Array.isArray(data)) throw new Error('fb');
    } catch(e) {
      const res = await sb.from('faqs').select('*').eq('is_published',true).order('sort_order',{ascending:true});
      data = res.data || [];
    }
    if (!data.length) { wrap.style.display='none'; return; }
    wrap.style.display = '';
    const EN = currentLang==='en';
    document.getElementById('faqList').innerHTML = data.map((f,i)=>`
      <details class="faq-item" ${i===0?'open':''}>
        <summary>${(EN&&f.question_en)?f.question_en:f.question}</summary>
        <p>${(EN&&f.answer_en)?f.answer_en:f.answer}</p>
      </details>`).join('');
  } catch(e){ wrap.style.display='none'; }
}

async function loadTestimonials(){
  try {
    let data;
    try {
      data = await getData('testimonials');
      if (!Array.isArray(data)) throw new Error('fb');
    } catch(e) {
      const res = await sb.from('testimonials').select('*').eq('is_published',true).order('sort_order',{ascending:true});
      data = res.data || [];
    }
    const grid = document.getElementById('testimonialsGrid');
    if (!Array.isArray(data) || !data.length) { grid.innerHTML = ''; document.getElementById('testimonials').style.display='none'; return; }
    grid.innerHTML = data.map(t => {
      const stars = '★'.repeat(t.rating||5) + '☆'.repeat(5-(t.rating||5));
      const avatar = t.avatar_url ? `<img src="${t.avatar_url}" alt="${t.name}" loading="lazy"/>` : (t.name||'د')[0];
      return `<div class="tst-card reveal">
        <div class="tst-head">
          <div class="tst-avatar">${avatar}</div>
          <div><div class="tst-name">${t.name}</div>${t.role?`<div class="tst-role">${t.role}</div>`:''}</div>
        </div>
        <div class="tst-stars">${stars}</div>
        <div class="tst-content">${t.content}</div>
        ${t.course?`<div class="tst-course">📚 ${t.course}</div>`:''}
      </div>`;
    }).join('');
    document.querySelectorAll('.reveal:not(.in)').forEach(el => io.observe(el));
  } catch(e){ document.getElementById('testimonials').style.display='none'; }
}
loadTestimonials();


/* ── وضعیت ورود کاربر در ناوبار ── */
(function(){
  try {
    const user = JSON.parse(localStorage.getItem('mm_user')||'null');
    if(user){
      const name = user.full_name ? user.full_name.split(' ')[0] : (user.username||'حساب من');
      ['navAuthBtn','navAuthBtnMobile'].forEach(id => {
        const btn = document.getElementById(id);
        if(!btn) return;
        btn.textContent = '👤 ' + name;
        btn.href = 'dashboard.html';
        btn.removeAttribute('data-i18n');
      });
    }
  } catch(e){}
})();



/* ── اعمال استایل‌های سفارشی ── */
(async function applyCustomStyles(){
  try {
    let data;
    try {
      data = await getData('site_content');
      if (!Array.isArray(data)) throw new Error('fb');
    } catch(e) {
      const res = await sb.from('site_content').select('key,value');
      data = res.data || [];
    }
    if (!Array.isArray(data) || !data.length) return;

    const s = {};
    data.forEach(x => s[x.key] = x.value);
    const root = document.documentElement.style;

    // رنگ‌های کلی
    if (s.st_accent) root.setProperty('--accent', s.st_accent);
    if (s.st_bg)     root.setProperty('--bg', s.st_bg);
    if (s.st_ink)    root.setProperty('--ink', s.st_ink);
    if (s.st_muted)  root.setProperty('--muted', s.st_muted);

    // نگاشت بخش‌ها به سلکتورها
    const map = {
      hero:         '.hero h1, #home h1',
      hero_sub:     '.hero .lead, #home .lead, .hero p.lead',
      section_h2:   '.section h2, section h2',
      eyebrow:      '.eyebrow',
      body:         '.section p, section p, .lead',
      course_title: '.course h3',
      course_price: '.course-price',
      work_title:   '.work .label .t',
      nav:          '.nav a, .nav ul a',
      footer:       'footer, .foot-bot, .foot-bot div',
    };

    let css = '';
    Object.entries(map).forEach(([id, sel]) => {
      const size = s['sty_' + id + '_size'];
      const color = s['sty_' + id + '_color'];
      const weight = s['sty_' + id + '_weight'];
      const rules = [];
      if (size)   rules.push('font-size:' + size + 'px !important');
      if (color)  rules.push('color:' + color + ' !important');
      if (weight) rules.push('font-weight:' + weight + ' !important');
      if (rules.length) css += sel + '{' + rules.join(';') + '}\n';
    });

    if (css) {
      const st = document.createElement('style');
      st.id = 'custom-site-styles';
      st.textContent = css;
      document.head.appendChild(st);
    }
  } catch(e){}
})();

/* ── VPN Popup ── */
(function(){
  if (!localStorage.getItem('mm_vpn_notice')) {
    setTimeout(() => {
      const p = document.getElementById('vpnPopup');
      if (p) p.classList.add('show');
    }, 1500);
  }
})();
function closeVpnPopup(){
  document.getElementById('vpnPopup').classList.remove('show');
  localStorage.setItem('mm_vpn_notice', '1');
}

/* ── PWA ── */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(()=>{}));
}

let deferredPrompt = null;
const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

function showPwaBanner(){
  if (isStandalone) return;
  if (localStorage.getItem('mm_pwa_dismissed')) return;
  if (!isMobile) return;
  const b = document.getElementById('pwaBanner');
  if (!b) return;
  if (isIOS) {
    document.getElementById('pwaHint').textContent = 'روی «نصب» بزن تا راهنمای نصب رو ببینی';
  }
  setTimeout(() => b.classList.add('show'), 4000);
}

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  showPwaBanner();
});

// سافاری روی آیفون رویداد beforeinstallprompt رو نمی‌فرسته — پس مستقیم نشون بده
if (isIOS && isMobile && !isStandalone) {
  window.addEventListener('load', () => setTimeout(showPwaBanner, 500));
}

function doInstall(){
  if (isIOS) {
    document.getElementById('pwaBanner').classList.remove('show');
    document.getElementById('iosGuide').classList.add('show');
    return;
  }
  if (!deferredPrompt) {
    dismissPwa();
    return;
  }
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then((res) => {
    deferredPrompt = null;
    document.getElementById('pwaBanner').classList.remove('show');
    if (res.outcome === 'accepted') localStorage.setItem('mm_pwa_dismissed','1');
  });
}

function dismissPwa(){
  document.getElementById('pwaBanner').classList.remove('show');
  localStorage.setItem('mm_pwa_dismissed','1');
}

function closeIosGuide(){
  document.getElementById('iosGuide').classList.remove('show');
  localStorage.setItem('mm_pwa_dismissed','1');
}

// iOS پشتیبانی از beforeinstallprompt نداره
if (isIOS && isMobile) showPwaBanner();

function installPWA(){ doInstall(); }
</script>
<!-- Lightbox ویدیو -->
<div class="lb" id="videoLb" onclick="if(event.target===this)closeLb()">
  <div class="lb-inner">
    <button class="lb-close" onclick="closeLb()">×</button>
    <div style="position:relative">
      <div id="lbLoading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#000;border-radius:8px;z-index:2">
        <div style="width:42px;height:42px;border:3px solid rgba(255,255,255,.15);border-top-color:var(--accent);border-radius:50%;animation:spin .8s linear infinite"></div>
      </div>
      <iframe id="lbFrame" class="lb-frame" allowfullscreen allow="autoplay; fullscreen" onload="document.getElementById('lbLoading').style.display='none'"></iframe>
    </div>
    <div class="lb-notice" id="lbNotice"></div>
  </div>
</div>

<!-- پاپ‌آپ اطلاع‌رسانی فیلترشکن -->
<div class="vpn-popup" id="vpnPopup">
  <div class="vpn-box">
    <div class="icon">🌐</div>
    <h3>برای تجربه بهتر</h3>
    <p>این سایت روی سرورهای داخل ایران میزبانی می‌شه.<br/>
    اگه فیلترشکن‌تون <strong>خاموش</strong> باشه، صفحات و ویدیوها خیلی سریع‌تر باز می‌شن.</p>
    <button class="vpn-btn" onclick="closeVpnPopup()">متوجه شدم</button>
    <div class="hint">این پیام فقط یک بار نمایش داده می‌شه</div>
  </div>
</div>

<!-- بنر نصب اپلیکیشن -->
<div class="pwa-banner" id="pwaBanner">
  <img src="assets/logo-192.png" alt="آکادمی مهدی محرابی" class="pwa-icon"/>
  <div class="pwa-text">
    <strong>آکادمی رو روی گوشیت داشته باش</strong>
    <span id="pwaHint">با یک لمس نصبش کن — سریع‌تر، بدون مرورگر</span>
  </div>
  <div class="pwa-actions">
    <button class="pwa-install" id="pwaInstallBtn" onclick="doInstall()">نصب</button>
    <button class="pwa-close" onclick="dismissPwa()">✕</button>
  </div>
</div>

<!-- راهنمای نصب iOS -->
<div class="ios-guide" id="iosGuide">
  <div class="ios-box">
    <button class="ios-close" onclick="closeIosGuide()">✕</button>
    <img src="assets/logo-192.png" alt="" style="width:64px;height:64px;margin-bottom:16px"/>
    <h3>نصب روی آیفون</h3>
    <p>برای اینکه آکادمی رو مثل یه اپلیکیشن روی گوشیت داشته باشی:</p>
    <div class="ios-steps">
      <div class="ios-step"><span class="num">۱</span> دکمه <strong>اشتراک‌گذاری</strong> پایین مرورگر رو بزن <span style="font-size:18px">􀈂</span></div>
      <div class="ios-step"><span class="num">۲</span> از لیست، <strong>Add to Home Screen</strong> رو انتخاب کن</div>
      <div class="ios-step"><span class="num">۳</span> بزن <strong>Add</strong> — تموم شد!</div>
    </div>
    <button class="vpn-btn" onclick="closeIosGuide()" style="margin-top:20px">فهمیدم</button>
  </div>
</div>

<!-- ── نوار خرید چسبان — بعد از عبور از هیرو ظاهر می‌شه ── -->

</body>
</html>
<?php
// بعد از اینکه صفحه کامل برای کاربر رفت، اگه داده‌ها بیشتر از ۶۰ ثانیه قدیمی بودن در پس‌زمینه تازه می‌شن
// (کاربر هیچ‌وقت منتظر این کار نمی‌مونه)
if (function_exists('mm_cache_age') && function_exists('mm_cache_finish')) {
    $__stale = [];
    foreach (['site_content', 'courses', 'projects', 'testimonials', 'faqs'] as $__t) {
        $__a = mm_cache_age($__t);
        if ($__a === null || $__a >= 60) { $__stale[] = $__t; }
    }
    if ($__stale && mm_cache_finish()) {
        foreach ($__stale as $__t) { mm_cache_refresh($__t); }
    }
}
