<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('GIST_TOKEN', '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');
define('GIST_ID', '6eaf110c6672bd20fc0920fe4ce03fc8'); 
$current_dir = __DIR__;
$result_file = $current_dir . '/result.txt';
$debug_file = $current_dir . '/debug.log';
function writeLog($message) {
    global $debug_file;
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}
function saveToGist($content) {
    $data = json_encode([
        'files' => [
            'result.txt' => [
                'content' => $content
            ]
        ]
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/gists/" . GIST_ID);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . GIST_TOKEN,
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    writeLog("Gist HTTP $httpCode");
    return $httpCode == 200;
}

$key = getenv('INPUT_KEY');
$link = getenv('INPUT_LINK');
writeLog("Key : $key");
writeLog("Link : $link");

if (empty($key) || empty($link)) {
    $error = "Thiếu Key Hoặc Link";
    writeLog("Lỗi : $error");
    file_put_contents($result_file, $error);
    saveToGist($error);
    die($error);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://id.traodoisub.com/api.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['link' => $link]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
writeLog("TDS HTTP $httpCode");
writeLog("Response : $response");

if ($httpCode != 200 || !$response) {
    $error = "Lỗi HTTP $httpCode - $curlError";
    writeLog("Lỗi : $error");
    file_put_contents($result_file, $error);
    saveToGist($error);
    die($error);
}

$data = json_decode($response, true);
$fb_id = $data['id'] ?? '';

if (!$fb_id) {
    $msg = $data['message'] ?? 'Không Lấy Được ID';
    writeLog("Lỗi : $msg");
    file_put_contents($result_file, "Lỗi : $msg");
    saveToGist("Lỗi : $msg");
    die("Lỗi : $msg");
}

writeLog("ID Facebook: $fb_id");
if (!preg_match('/^(61|1000)/', $fb_id)) {
    $error = "ID $fb_id Không Hợp Lệ - Phải Bắt Đầu Bằng 61 Hoặc 1000";
    writeLog("Lỗi : $error");
    file_put_contents($result_file, $error);
    saveToGist($error);
    die($error);
}

$smm_key = 'fdded2a450ab33c38764ceb5a3c971ef5368cc708bc5a09e9e450a7118df65d2';
$smm_data = [
    'key' => $smm_key,
    'action' => 'add',
    'service' => '68861',
    'link' => $fb_id,
    'quantity' => 100
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://smmlikevip.com/api/v2");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($smm_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
writeLog("SMM HTTP $httpCode");
writeLog("SMM Result : $result");

if ($httpCode != 200) {
    $error = "Lỗi SMM HTTP $httpCode";
    writeLog("Lỗi : $error");
    file_put_contents($result_file, $error);
    saveToGist($error);
    die($error);
}

$smm_result = json_decode($result, true);
if (!$smm_result) {
    $error = "Lỗi Parse JSON : $result";
    writeLog("Lỗi : $error");
    file_put_contents($result_file, $error);
    saveToGist($error);
    die($error);
}

if (isset($smm_result['status']) && $smm_result['status'] == 'success') {
    $output = "[</>] Tăng Follow Facebook Thành Công - Vui Lòng Chờ Xử Lý\n";
} else {
    $error_msg = $smm_result['msg'] ?? $smm_result['error'] ?? '[</>] Lỗi Không Xác Định';
    if (preg_match('/Vui lòng đợi thời gian còn lại: (.+)/', $error_msg, $matches)) {
        $output = "[</>] Quay Lại Sau " . $matches[1];
    } else {
        $output = "[</>] Lỗi : $error_msg";
    }
}

writeLog("Kết Quả : $output");
file_put_contents($result_file, $output);

if (saveToGist($output)) {
    writeLog("Đã Lưu Vào Gist Thành Công");
} else {
    writeLog("Không Lưu Được Vào Gist");
}

echo "OK - Đã Xử Lý Xong, Kết Quả Lưu Trong Gist\n";
?>
