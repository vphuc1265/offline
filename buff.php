<?php
// buff.php - Chạy trong GitHub Actions
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Đường dẫn tuyệt đối trong môi trường GitHub Actions
$current_dir = __DIR__;
$result_file = $current_dir . '/result.txt';
$debug_file = $current_dir . '/debug.log';

// Hàm ghi log
function writeLog($message) {
    global $debug_file;
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

writeLog("=== BẮT ĐẦU ===");

// Lấy input từ environment
$key = getenv('INPUT_KEY');
$link = getenv('INPUT_LINK');

writeLog("Key: $key");
writeLog("Link: $link");

if (empty($key) || empty($link)) {
    $error = "Thiếu key hoặc link";
    writeLog("LỖI: $error");
    file_put_contents($result_file, "LỖI: $error");
    die($error);
}

// 1. Lấy ID từ traodoisub
writeLog("Đang gọi traodoisub...");
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

writeLog("Traodoisub HTTP: $httpCode");
writeLog("Response: $response");

if ($httpCode != 200 || !$response) {
    $error = "Lỗi traodoisub: HTTP $httpCode - $curlError";
    writeLog("LỖI: $error");
    file_put_contents($result_file, $error);
    die($error);
}

$data = json_decode($response, true);
$fb_id = $data['id'] ?? '';

if (!$fb_id) {
    $msg = $data['message'] ?? 'Không lấy được ID';
    writeLog("LỖI: $msg");
    file_put_contents($result_file, "LỖI: $msg");
    die("Lỗi: $msg");
}

writeLog("ID Facebook: $fb_id");

// 2. Validate ID
if (!preg_match('/^(61|1000)/', $fb_id)) {
    $error = "ID $fb_id không hợp lệ - Phải bắt đầu bằng 61 hoặc 1000";
    writeLog("LỖI: $error");
    file_put_contents($result_file, $error);
    die($error);
}

// 3. Gọi SMM API
writeLog("Đang gọi SMM API...");
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
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

writeLog("SMM HTTP: $httpCode");
writeLog("SMM Result: $result");

if ($httpCode != 200) {
    $error = "Lỗi SMM API: HTTP $httpCode";
    writeLog("LỖI: $error");
    file_put_contents($result_file, $error);
    die($error);
}

// 4. Parse kết quả SMM
$smm_result = json_decode($result, true);
if (!$smm_result) {
    writeLog("LỖI: Không parse được JSON");
    file_put_contents($result_file, "Lỗi parse JSON: $result");
    die("Lỗi parse JSON");
}

// 5. Định dạng kết quả
if (isset($smm_result['status']) && $smm_result['status'] == 'success') {
    $output = "[</>] Tăng Follow Facebook Thành Công - Vui Lòng Chờ Xử Lý\n";
    if (isset($smm_result['order'])) {
        $output .= "[</>] Mã Đơn: " . $smm_result['order'];
    }
} else {
    $error_msg = $smm_result['msg'] ?? $smm_result['error'] ?? 'Lỗi không xác định';
    if (preg_match('/Vui lòng đợi thời gian còn lại: (.+)/', $error_msg, $matches)) {
        $output = "[</>] Quay Lại Sau " . $matches[1];
    } else {
        $output = "[</>] Lỗi: $error_msg";
    }
}

// 6. Ghi kết quả vào file
writeLog("Kết quả cuối: $output");
file_put_contents($result_file, $output);
writeLog("=== KẾT THÚC ===");

echo "OK - Đã xử lý xong\n";
?>
