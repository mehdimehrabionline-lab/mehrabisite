<?php
/**
 * تنظیمات مشترک
 */

// Supabase
define('SUPABASE_URL', 'https://rknsiuyfhxnmhnfesfhh.supabase.co');

// کلید anon از فایل جاوااسکریپت خونده می‌شه
function getAnonKey() {
    static $key = null;
    if ($key !== null) return $key;
    $f = dirname(__DIR__) . '/supabase-config.js';
    if (file_exists($f) && preg_match("/SUPABASE_ANON_KEY\s*=\s*['\"]([^'\"]+)['\"]/", file_get_contents($f), $m)) {
        $key = $m[1];
    } else {
        $key = '';
    }
    return $key;
}

// کلید service_role — این رو خودت پر کن
define('SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJrbnNpdXlmaHhubWhuZmVzZmhoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MDY4MDU5NywiZXhwIjoyMDk2MjU2NTk3fQ.4wz_dLyhUgDWoB4uvB_3mUfYIhP-CF0T0fZ1VMx4cjY');

// زیبال
define('ZIBAL_MERCHANT', '6a97068a51c8b0c88b8ca509');
define('ZIBAL_CALLBACK', 'https://mehdimehrabi.ir/payment-callback.html');

// SMS.ir — کلید API را اینجا بگذارید
define('SMSIR_KEY', 'hq30VT9W0A46xjUJbqYgGvOILIBkZrNzM9UsNp6Gwbe4xIdu');

// درخواست به Supabase
function sb_request($path, $method = 'GET', $body = null, $useService = true) {
    $key = $useService ? SERVICE_KEY : getAnonKey();
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $path);
    $headers = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
    ];
    if ($body !== null) $headers[] = 'Prefer: return=representation';

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

// نشست رو تأیید می‌کنه و ردیف کاربر (id, phone, full_name, ...) رو برمی‌گردونه؛
// اگه توکن نامعتبر/منقضی/باطل بود null برمی‌گردونه.
// این جایگزین اعتماد صرف به شماره موبایل (?p=...) شده — قبلاً هرکس شماره‌ی یه نفر رو می‌دونست
// می‌تونست جاش وانمود کنه؛ الان حتماً به یه توکن نشستِ معتبر (یعنی ورود واقعی) نیاز داره.
function sb_require_session($token) {
    if (!$token) return null;
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $r = sb_request('user_sessions?token=eq.'.rawurlencode($token).'&revoked=eq.false&expires_at=gte.'.$now.'&select=id,academy_users(id,phone,full_name,email,username,age,job,goal,how_found)');
    if (empty($r['data'][0]) || empty($r['data'][0]['academy_users'])) return null;
    $row = $r['data'][0];
    // آخرین فعالیت رو به‌روز کن (بی‌صدا — اگه خطا خورد مهم نیست، جلوی کاربر رو نمی‌گیره)
    sb_request('user_sessions?id=eq.'.rawurlencode($row['id']), 'PATCH', ['last_seen_at' => gmdate('Y-m-d\TH:i:s\Z')]);
    return $row['academy_users'];
}
function json_out($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
