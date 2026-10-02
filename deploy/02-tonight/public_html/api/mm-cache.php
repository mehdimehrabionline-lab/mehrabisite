<?php
/**
 * کش و دسترسی «تحمل‌پذیر» به Supabase برای مسیر دانشجو (دوره‌ها، ویدیوها، پیوست‌ها)
 *
 * اصول:
 *  • تا وقتی Supabase سالم است، رفتار و خروجی دقیقاً مثل قبل است؛ فقط سریع‌تر
 *    (داده‌ی تازه‌ی چند‌ثانیه‌ای از کش خوانده می‌شود، درخواست‌های مستقل موازی می‌روند).
 *  • اگر Supabase در دسترس نبود (قطعی اینترنت بین‌الملل، DNS کند، ...) و برای آن داده «کش قدیمی» داریم،
 *    همان را برمی‌گردانیم تا دانشجوی فعلی بتواند ویدیو ببیند.
 *  • اگر نه کش داریم نه Supabase جواب می‌دهد، خطای ۵۰۳ با پیام روشن برمی‌گردد (نه خروج کاربر از حساب).
 *  • این فایل هیچ چیزی را در دیتابیس تغییر نمی‌دهد جز «آخرین فعالیت نشست» (حداکثر هر ۵ دقیقه).
 *  • پرداخت و ورود به این فایل وابسته نیستند.
 *
 * شبیه‌سازی قطعی برای تست: یک فایل خالی به نام  mm-sb-down.flag  کنار index.php بساز (بعد از تست حذفش کن).
 */
if (!defined('MM_CACHE_LOADED')) {
define('MM_CACHE_LOADED', 1);

define('MM_FRESH_SESSION', 60);       // ثانیه: نشست معتبر کش‌شده بدون سؤال از Supabase
define('MM_FRESH_ACCESS_LIST', 10);   // لیست دسترسی‌های کاربر (داشبورد) — کوتاه، تا خریدِ تازه فوراً دیده شود
define('MM_FRESH_ACCESS_HIT', 60);    // وقتی دوره‌ی درخواستی داخل لیست کش‌شده هست
define('MM_FRESH_VIDEOS', 60);        // لیست ویدیوهای یک دوره
define('MM_FRESH_ROW', 300);          // ردیف ویدیو / پیوست
define('MM_STALE_MAX', 1209600);      // حداکثر عمر داده‌ی کهنه‌ای که موقع قطعی استفاده می‌شود (۱۴ روز)
define('MM_BREAKER_SECONDS', 30);     // بعد از خطای شبکه، این‌قدر سراغ Supabase نمی‌رویم (فقط اگر کش داریم)

/* ───────────── ذخیره‌ی فایلی (خارج از public_html) ───────────── */

function mm_dir() {
    static $dir = false;
    if ($dir !== false) return $dir;
    $cands = [
        dirname(__DIR__, 2) . '/mm_cache',
        rtrim(sys_get_temp_dir(), '/\\') . '/mm_cache_' . substr(md5(__DIR__), 0, 8),
    ];
    foreach ($cands as $d) {
        if (!is_dir($d)) @mkdir($d, 0700, true);
        if (is_dir($d) && is_writable($d)) return $dir = $d;
    }
    return $dir = null;
}

function mm_file($key) {
    $d = mm_dir();
    return $d ? $d . '/' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $key) . '.json' : null;
}

/** [ 'd' => داده, 'age' => سن به ثانیه ] یا null */
function mm_get($key) {
    $f = mm_file($key);
    if (!$f || !is_file($f)) return null;
    $raw = @file_get_contents($f);
    if ($raw === false || $raw === '') return null;
    $j = json_decode($raw, true);
    if (!is_array($j) || !isset($j['t']) || !array_key_exists('d', $j)) return null;
    return ['d' => $j['d'], 'age' => max(0, time() - (int)$j['t'])];
}

function mm_put($key, $data) {
    $f = mm_file($key);
    if (!$f) return false;
    $tmp = $f . '.' . getmypid() . '.' . mt_rand(1000, 9999) . '.tmp';
    if (@file_put_contents($tmp, json_encode(['t' => time(), 'd' => $data], JSON_UNESCAPED_UNICODE)) === false) return false;
    @chmod($tmp, 0600);
    if (!@rename($tmp, $f)) { @unlink($tmp); return false; }
    return true;
}

function mm_del($key) {
    $f = mm_file($key);
    if ($f && is_file($f)) @unlink($f);
}

/** پاک‌سازی گاه‌به‌گاه فایل‌های خیلی قدیمی */
function mm_gc() {
    if (mt_rand(1, 400) !== 1) return;
    $d = mm_dir();
    if (!$d) return;
    $limit = time() - 30 * 86400;
    foreach ((array)@glob($d . '/*.json') as $f) {
        if (@filemtime($f) < $limit) @unlink($f);
    }
}

/* ───────────── قطع‌کننده‌ی مدار + شبیه‌ساز قطعی ───────────── */

function mm_flag_down() {
    return is_file(dirname(__DIR__) . '/mm-sb-down.flag');
}
function mm_breaker_open() {
    $f = mm_file('breaker');
    if (!$f || !is_file($f)) return false;
    $j = json_decode((string)@file_get_contents($f), true);
    return is_array($j) && isset($j['until']) && $j['until'] > time();
}
function mm_breaker_trip() {
    $f = mm_file('breaker');
    if ($f) @file_put_contents($f, json_encode(['until' => time() + MM_BREAKER_SECONDS]), LOCK_EX);
}
function mm_breaker_reset() {
    $f = mm_file('breaker');
    if ($f && is_file($f)) @unlink($f);
}

/* ───────────── درخواست به Supabase (چندتا هم‌زمان) ───────────── */

/**
 * @param array $reqs  هر عضو: ['path'=>..., 'method'=>'GET', 'body'=>null]
 * @param bool  $haveFallback  اگر true یعنی کش قدیمی داریم → تایم‌اوت کوتاه و احترام به قطع‌کننده؛
 *                             اگر false تایم‌اوت بلندتر (مثل رفتار قبلی) تا بدون کش هم کار کند.
 * @return array  به‌همان ترتیب: ['ok'=>bool, 'code'=>int, 'data'=>mixed]
 */
function mm_sb_multi($reqs, $haveFallback) {
    $res = [];
    $fail = ['ok' => false, 'code' => 0, 'data' => null];
    if (mm_flag_down() || ($haveFallback && mm_breaker_open())) {
        foreach ($reqs as $i => $r) $res[$i] = $fail;
        return $res;
    }
    $mh = curl_multi_init();
    $hs = [];
    foreach ($reqs as $i => $r) {
        $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $r['path']);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => isset($r['method']) ? $r['method'] : 'GET',
            CURLOPT_CONNECTTIMEOUT => $haveFallback ? 3 : 8,
            CURLOPT_TIMEOUT => $haveFallback ? 6 : 15,
            CURLOPT_HTTPHEADER => ['apikey: ' . SERVICE_KEY, 'Authorization: Bearer ' . SERVICE_KEY, 'Content-Type: application/json'],
        ];
        if (isset($r['body'])) $opts[CURLOPT_POSTFIELDS] = json_encode($r['body'], JSON_UNESCAPED_UNICODE);
        curl_setopt_array($ch, $opts);
        curl_multi_add_handle($mh, $ch);
        $hs[$i] = $ch;
    }
    do {
        $st = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 0.2);
    } while ($running && $st === CURLM_OK);

    $netFail = false; $anyOk = false;
    foreach ($hs as $i => $ch) {
        $raw = curl_multi_getcontent($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $data = ($raw !== null && $raw !== '') ? json_decode($raw, true) : null;
        $ok = ($errno === 0 && $code >= 200 && $code < 300);
        $res[$i] = ['ok' => $ok, 'code' => $code, 'data' => $data];
        if ($errno !== 0 || $code === 0 || $code >= 500) $netFail = true;
        elseif ($ok) $anyOk = true;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    if ($netFail) mm_breaker_trip(); elseif ($anyOk) mm_breaker_reset();
    return $res;
}

function mm_sb_one($path, $haveFallback) {
    $r = mm_sb_multi([['path' => $path]], $haveFallback);
    return $r[0];
}

/** پاسخ موفقِ «لیست» (ممکن است خالی باشد) */
function mm_ok_list($r) {
    return $r['ok'] && is_array($r['data']) && (empty($r['data']) || isset($r['data'][0]));
}

function mm_list_has($list, $courseId) {
    if (!is_array($list)) return false;
    foreach ($list as $a) if (is_array($a) && isset($a['course_id']) && $a['course_id'] === $courseId) return true;
    return false;
}

/* ───────────── کارهای بعد از ارسال جواب ───────────── */

function mm_finish() {
    if (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); }
    elseif (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
}
function mm_after($fn) {
    register_shutdown_function($fn);
}

/* ───────────── نشست ───────────── */

/**
 * @return array|null|false  کاربر | null = نشست قطعاً نامعتبر | false = Supabase در دسترس نیست و کش هم نداریم
 */
function mm_session_user($token) {
    if (!is_string($token) || $token === '' || strlen($token) > 200) return null;
    $key = 's_' . hash('sha256', $token);
    $now = time();
    $c = mm_get($key);
    $valid = $c && is_array($c['d']) && !empty($c['d']['user']) && (isset($c['d']['exp']) ? $c['d']['exp'] : 0) > $now;
    if ($valid && $c['age'] < MM_FRESH_SESSION) return $c['d']['user'];
    $have = $valid && $c['age'] < MM_STALE_MAX;

    $iso = gmdate('Y-m-d\TH:i:s\Z');
    $r = mm_sb_one('user_sessions?token=eq.' . rawurlencode($token) . '&revoked=eq.false&expires_at=gte.' . $iso
        . '&select=id,expires_at,last_seen_at,academy_users(id,phone,full_name,email,username,age,job,goal,how_found)', $have);

    if (mm_ok_list($r)) {
        $row = isset($r['data'][0]) ? $r['data'][0] : null;
        if (!$row || empty($row['academy_users'])) { mm_del($key); return null; }
        $user = $row['academy_users'];
        $exp = !empty($row['expires_at']) ? strtotime($row['expires_at']) : ($now + 3600);
        mm_put($key, ['user' => $user, 'exp' => $exp, 'sid' => $row['id']]);
        $ls = !empty($row['last_seen_at']) ? strtotime($row['last_seen_at']) : 0;
        if ($now - $ls > 300) {
            $sid = $row['id'];
            mm_after(function () use ($sid) {
                mm_sb_multi([['path' => 'user_sessions?id=eq.' . rawurlencode($sid), 'method' => 'PATCH', 'body' => ['last_seen_at' => gmdate('Y-m-d\TH:i:s\Z')]]], true);
            });
        }
        mm_gc();
        return $user;
    }
    if ($have) return $c['d']['user'];   // Supabase جواب نداد → کش قدیمی
    return false;
}

/* ───────────── دسترسی به دوره‌ها ───────────── */

/**
 * لیست دسترسی‌های فعال کاربر. اگر $mustContain داده شود و در کش نباشد، حتماً از Supabase تازه می‌پرسد
 * (تا خریدِ همین الان هیچ‌وقت «فاقد دسترسی» دیده نشود).
 * @return array|false  false = در دسترس نیست و کش هم نداریم
 */
function mm_access_list($userId, $mustContain = null) {
    $key = 'a_' . $userId;
    $c = mm_get($key);
    $list = ($c && is_array($c['d'])) ? $c['d'] : null;
    if ($list !== null) {
        if ($mustContain === null) {
            if ($c['age'] < MM_FRESH_ACCESS_LIST) return $list;
        } elseif ($c['age'] < MM_FRESH_ACCESS_HIT && mm_list_has($list, $mustContain)) {
            return $list;
        }
    }
    $have = $list !== null && $c['age'] < MM_STALE_MAX;
    $r = mm_sb_one('course_access?user_id=eq.' . rawurlencode($userId) . '&is_active=eq.true&select=course_id,granted_at', $have);
    if (mm_ok_list($r)) { mm_put($key, $r['data']); return $r['data']; }
    if ($have) return $list;
    return false;
}

/* ───────────── ویدیوها ───────────── */

function mm_videos_paths($courseId) {
    return 'course_videos?course_id=eq.' . rawurlencode($courseId)
        . '&select=id,title,description,duration,sort_order,file_name,course_attachments(id,title,file_size,sort_order,file_name,video_id)'
        . '&order=sort_order.asc';
}

/** حالت پشتیبان مثل کد قبلی: اگر embed کار نکرد، ویدیوها و پیوست‌ها جدا گرفته می‌شوند */
function mm_videos_fallback($courseId, $have) {
    $v = mm_sb_one('course_videos?course_id=eq.' . rawurlencode($courseId) . '&select=id,title,description,duration,sort_order,file_name&order=sort_order.asc', $have);
    if (!mm_ok_list($v)) return null;
    $videos = $v['data'];
    if ($videos) {
        $ids = array_map(function ($x) { return $x['id']; }, $videos);
        $at = mm_sb_one('course_attachments?video_id=in.(' . implode(',', array_map('rawurlencode', $ids)) . ')&select=id,title,file_size,sort_order,video_id,file_name&order=sort_order.asc', $have);
        $byVid = [];
        if (mm_ok_list($at)) foreach ($at['data'] as $row) $byVid[$row['video_id']][] = $row;
        foreach ($videos as &$vv) $vv['course_attachments'] = isset($byVid[$vv['id']]) ? $byVid[$vv['id']] : [];
        unset($vv);
    }
    return $videos;
}

/**
 * دسترسی کاربر به دوره + لیست ویدیوهای دوره، با یک رفت‌وبرگشت هم‌زمان (به‌جای پشت‌سرهم).
 * @return array ['status'=>'ok'|'unavailable', 'has'=>bool, 'videos'=>array]
 */
function mm_course_bundle($userId, $courseId) {
    $ka = 'a_' . $userId;
    $kv = 'v_' . $courseId;
    $ca = mm_get($ka);
    $cv = mm_get($kv);
    $aList = ($ca && is_array($ca['d'])) ? $ca['d'] : null;
    $vList = ($cv && is_array($cv['d'])) ? $cv['d'] : null;

    $aHit = $aList !== null && $ca['age'] < MM_FRESH_ACCESS_HIT && mm_list_has($aList, $courseId);
    $vHit = $vList !== null && $cv['age'] < MM_FRESH_VIDEOS;
    if ($aHit && $vHit) return ['status' => 'ok', 'has' => true, 'videos' => $vList];

    $aHave = $aList !== null && $ca['age'] < MM_STALE_MAX;
    $vHave = $vList !== null && $cv['age'] < MM_STALE_MAX;

    $reqs = []; $idx = [];
    if (!$aHit) { $idx['a'] = count($reqs); $reqs[] = ['path' => 'course_access?user_id=eq.' . rawurlencode($userId) . '&is_active=eq.true&select=course_id,granted_at']; }
    if (!$vHit) { $idx['v'] = count($reqs); $reqs[] = ['path' => mm_videos_paths($courseId)]; }
    // کش قدیمی برای همه‌ی آنچه لازم است هست؟ (برای تعیین تایم‌اوت)
    $have = (!isset($idx['a']) || $aHave) && (!isset($idx['v']) || $vHave);
    $res = mm_sb_multi($reqs, $have);

    // دسترسی
    $access = null;   // لیست نهایی
    if ($aHit) $access = $aList;
    else {
        $r = $res[$idx['a']];
        if (mm_ok_list($r)) { mm_put($ka, $r['data']); $access = $r['data']; }
        elseif ($aHave) $access = $aList;
    }
    // ویدیوها
    $videos = null;
    if ($vHit) $videos = $vList;
    else {
        $r = $res[$idx['v']];
        if (mm_ok_list($r)) { mm_put($kv, $r['data']); $videos = $r['data']; }
        elseif ($r['ok'] === false && $r['code'] >= 400 && $r['code'] < 500) {
            // embed کار نکرده (خطای ۴xx، نه قطعی شبکه) → حالت پشتیبان مثل کد قبلی
            $fb = mm_videos_fallback($courseId, $vHave);
            if (is_array($fb)) { mm_put($kv, $fb); $videos = $fb; }
        }
        if ($videos === null && $vHave) $videos = $vList;
    }
    if ($access === null) return ['status' => 'unavailable', 'has' => false, 'videos' => []];
    $has = mm_list_has($access, $courseId);
    if (!$has) return ['status' => 'ok', 'has' => false, 'videos' => []];
    if ($videos === null) return ['status' => 'unavailable', 'has' => true, 'videos' => []];
    return ['status' => 'ok', 'has' => true, 'videos' => $videos];
}

/* ───────────── ردیف ویدیو / پیوست (برای videotoken و attachtoken) ───────────── */

/** @return array|null|false  ردیف | null = وجود ندارد | false = در دسترس نیست */
function mm_row($table, $id, $select) {
    $key = 'r_' . $table . '_' . $id;
    $c = mm_get($key);
    $has = $c && is_array($c['d']);
    if ($has && $c['age'] < MM_FRESH_ROW) return $c['d'];
    $haveStale = $has && $c['age'] < MM_STALE_MAX;
    $r = mm_sb_one($table . '?id=eq.' . rawurlencode($id) . '&select=' . $select, $haveStale);
    if (mm_ok_list($r)) {
        if (empty($r['data'])) { mm_del($key); return null; }
        mm_put($key, $r['data'][0]);
        return $r['data'][0];
    }
    if ($haveStale) return $c['d'];
    return false;
}

/* ───────────── جایگزین موقع قطعی: پیدا کردن ویدیو/پیوست از لیست ویدیوهای کش‌شده‌ی دوره ───────────── */

/** ردیف ویدیو از روی لیست کش‌شده‌ی همان دوره (وقتی ردیف جدا در دسترس نیست) */
function mm_video_from_cache($courseId, $videoId) {
    if ($courseId === '') return null;
    $cv = mm_get('v_' . $courseId);
    if (!$cv || !is_array($cv['d']) || $cv['age'] > MM_STALE_MAX) return null;
    foreach ($cv['d'] as $v) {
        if (is_array($v) && isset($v['id']) && $v['id'] === $videoId) {
            return ['id' => $v['id'], 'course_id' => $courseId, 'file_name' => isset($v['file_name']) ? $v['file_name'] : null];
        }
    }
    return null;
}

/** پیوست را در همه‌ی لیست‌های کش‌شده‌ی دوره‌ها پیدا می‌کند → ['att'=>..., 'course_id'=>...] یا null */
function mm_attachment_from_cache($attachId) {
    $d = mm_dir();
    if (!$d) return null;
    foreach ((array)@glob($d . '/v_*.json') as $f) {
        $j = json_decode((string)@file_get_contents($f), true);
        if (!is_array($j) || !isset($j['d']) || !is_array($j['d']) || (time() - (int)(isset($j['t']) ? $j['t'] : 0)) > MM_STALE_MAX) continue;
        $courseId = substr(basename($f, '.json'), 2);
        foreach ($j['d'] as $v) {
            if (!is_array($v) || empty($v['course_attachments']) || !is_array($v['course_attachments'])) continue;
            foreach ($v['course_attachments'] as $a) {
                if (is_array($a) && isset($a['id']) && $a['id'] === $attachId && !empty($a['file_name'])) {
                    return ['att' => ['id' => $a['id'], 'title' => isset($a['title']) ? $a['title'] : '', 'file_name' => $a['file_name'], 'video_id' => $v['id']], 'course_id' => $courseId];
                }
            }
        }
    }
    return null;
}

} // MM_CACHE_LOADED
