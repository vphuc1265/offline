<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$key = getenv('INPUT_KEY');
$link = getenv('INPUT_LINK');
if (empty($key) || empty($link)) {
    $error = "Thiếu Key Hoặc Link";
    file_put_contents('result.txt', $error);
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

if ($httpCode != 200 || !$response) {
    $error = "Lỗi HTTP $httpCode - $curlError";
    file_put_contents('result.txt', $error);
    die($error);
}

$data = json_decode($response, true);
$fb_id = $data['id'] ?? '';

if (!$fb_id) {
    $msg = $data['message'] ?? 'Không Lấy Được ID';
    file_put_contents('result.txt', "Lỗi : $msg");
    die("Lỗi : $msg");
}

if (!preg_match('/^(61|1000)/', $fb_id)) {
    $error = "ID $fb_id Không Hợp Lệ - Phải Bắt Đầu Bằng 61 Hoặc 1000";
    file_put_contents('result.txt', $error);
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
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    $error = "Lỗi HTTP $httpCode";
    file_put_contents('result.txt', $error);
    die($error);
}

$smm_result = json_decode($result, true);
if (!$smm_result) {
    file_put_contents('result.txt', "Lỗi Parse JSON");
    die("Lỗi Parse JSON");
}

if (isset($smm_result['status']) && $smm_result['status'] == 'success') {
    $output = "[</>] Tăng Follow Facebook Thành Công - Vui Lòng Chờ Xử Lý\n";
} else {
    $error_msg = $smm_result['msg'] ?? $smm_result['error'] ?? '[</>] Lỗi Không Xác Định';
    if (preg_match('/Vui lòng đợi thời gian còn lại: (.+)/', $error_msg, $matches)) {
        $output = "[</>] Quay Lại Sau " . $matches[1];
    } else {
        $output = "$error_msg";
    }
}

file_put_contents('result.txt', $output);
echo "OK - Đã Xử Lý Xong\n";
?>
