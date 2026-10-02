<?php
/**
 * آپلود تکه‌تکه‌ی فایل‌های بزرگ (ویدیو/پیوست) — فقط ادمین
 * محدودیت ۲۵۶ مگابایتی PHP را دور می‌زند: فایل در تکه‌های ~۸ مگابایتی فرستاده و در سرور به‌هم چسبانده می‌شود.
 * اگر وسط راه نت قطع شود، پنل از همان تکه‌ی قطع‌شده ادامه می‌دهد (resumable).
 *
 * POST (multipart) با فیلد action:
 *   init   : kind=video|attach, ext, size                → {ok, upload_id, next}
 *   status : upload_id                                   → {ok, next}
 *   chunk  : upload_id, offset, فایل chunk               → {ok, next}
 *   finish : upload_id                                   → {ok, file_name, size}
 *   abort  : upload_id                                   → {ok}
 * فایل نهایی دقیقاً مثل آپلود قبلی در protected_videos با نام تصادفی ذخیره می‌شود.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/api/config.php';

define('DEST_DIR', dirname(__DIR__) . '/protected_videos');
define('MAX_TOTAL', 10 * 1024 * 1024 * 1024);   // ۱۰ گیگابایت
define('MAX_CHUNK', 32 * 1024 * 1024);          // ۳۲ مگابایت (پنل ۸ مگابایتی می‌فرستد)

function out($a, $c = 200) { http_response_code($c); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

// احراز هویت ادمین با توکن Supabase (مثل بقیه‌ی آپلودها)
$auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
$token = trim(str_replace('Bearer ', '', $auth));
if (!$token) out(['ok'=>false,'error'=>'دسترسی غیرمجاز'], 401);
$ch = curl_init(SUPABASE_URL . '/auth/v1/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: '.getAnonKey(), 'Authorization: Bearer '.$token]);
$userRes = json_decode(curl_exec($ch), true);
curl_close($ch);
if (empty($userRes['id'])) out(['ok'=>false,'error'=>'توکن نامعتبر'], 401);

if (!is_dir(DEST_DIR) && !@mkdir(DEST_DIR, 0750, true)) out(['ok'=>false,'error'=>'پوشه‌ی protected_videos ساخته نشد'], 500);

$ALLOWED = [
    'video'  => ['mp4','m4v','webm'],
    'attach' => ['pdf','jpg','jpeg','png','webp','zip','doc','docx','ppt','pptx'],
];

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id = isset($_POST['upload_id']) ? $_POST['upload_id'] : '';
if ($action !== 'init' && !preg_match('/^[a-f0-9]{24}$/', $id)) out(['ok'=>false,'error'=>'شناسه‌ی آپلود نامعتبر'], 400);
$part = DEST_DIR . '/.tmp-' . $id . '.part';
$meta = DEST_DIR . '/.tmp-' . $id . '.json';
function readMeta($f) { $m = is_file($f) ? json_decode((string)file_get_contents($f), true) : null; return is_array($m) ? $m : null; }

if ($action === 'init') {
    $kind = isset($_POST['kind']) ? $_POST['kind'] : 'video';
    $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', isset($_POST['ext']) ? $_POST['ext'] : ''));
    $size = (int)(isset($_POST['size']) ? $_POST['size'] : 0);
    if (!isset($ALLOWED[$kind]) || !in_array($ext, $ALLOWED[$kind], true)) out(['ok'=>false,'error'=>'نوع فایل مجاز نیست'], 400);
    if ($size < 1 || $size > MAX_TOTAL) out(['ok'=>false,'error'=>'حجم فایل نامعتبر'], 400);
    $free = @disk_free_space(DEST_DIR);
    if ($free !== false && $free < $size + 200 * 1024 * 1024) out(['ok'=>false,'error'=>'فضای کافی روی هاست نیست'], 507);
    // پاک‌سازی آپلودهای نیمه‌کاره‌ی قدیمی‌تر از ۲۴ ساعت
    foreach (glob(DEST_DIR . '/.tmp-*') ?: [] as $f) { if (@filemtime($f) < time() - 86400) @unlink($f); }
    $id = bin2hex(random_bytes(12));
    file_put_contents(DEST_DIR . '/.tmp-' . $id . '.json', json_encode(['kind'=>$kind,'ext'=>$ext,'size'=>$size,'t'=>time()]));
    @file_put_contents(DEST_DIR . '/.tmp-' . $id . '.part', '');
    out(['ok'=>true,'upload_id'=>$id,'next'=>0]);
}

$m = readMeta($meta);
if (!$m) out(['ok'=>false,'error'=>'آپلود پیدا نشد (شاید منقضی شده)'], 404);
clearstatcache();
$have = is_file($part) ? filesize($part) : 0;

if ($action === 'status') out(['ok'=>true,'next'=>$have,'size'=>$m['size']]);

if ($action === 'abort') { @unlink($part); @unlink($meta); out(['ok'=>true]); }

if ($action === 'chunk') {
    $offset = (int)(isset($_POST['offset']) ? $_POST['offset'] : -1);
    if ($offset !== $have) out(['ok'=>false,'error'=>'ترتیب تکه‌ها به‌هم خورد','next'=>$have], 409);   // پنل از next ادامه می‌دهد
    if (empty($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) out(['ok'=>false,'error'=>'تکه دریافت نشد (کد '.(isset($_FILES['chunk']) ? $_FILES['chunk']['error'] : '-').')'], 400);
    $len = filesize($_FILES['chunk']['tmp_name']);
    if ($len < 1 || $len > MAX_CHUNK || $have + $len > $m['size']) out(['ok'=>false,'error'=>'اندازه‌ی تکه نامعتبر'], 400);
    $in = fopen($_FILES['chunk']['tmp_name'], 'rb'); $outf = fopen($part, 'ab');
    if (!$in || !$outf || !flock($outf, LOCK_EX)) out(['ok'=>false,'error'=>'نوشتن روی دیسک ممکن نشد'], 500);
    stream_copy_to_stream($in, $outf); fflush($outf); flock($outf, LOCK_UN); fclose($in); fclose($outf);
    clearstatcache(); out(['ok'=>true,'next'=>filesize($part)]);
}

if ($action === 'finish') {
    if ($have !== (int)$m['size']) out(['ok'=>false,'error'=>'فایل کامل نیست','next'=>$have], 409);
    $name = bin2hex(random_bytes(12)) . '.' . $m['ext'];
    $dest = DEST_DIR . '/' . $name;
    if (!@rename($part, $dest)) out(['ok'=>false,'error'=>'ذخیره‌ی نهایی ناموفق'], 500);
    @chmod($dest, 0640); @unlink($meta);
    out(['ok'=>true,'file_name'=>$name,'size'=>filesize($dest)]);
}

out(['ok'=>false,'error'=>'عملیات نامعتبر'], 400);
