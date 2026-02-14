<?php

$key = getenv('INPUT_KEY');
$link = getenv('INPUT_LINK');

if (empty($key) || empty($link)) {
    die("Thiếu key hoặc link");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://id.traodoisub.com/api.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['link' => $link]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("Lỗi HTTP $httpCode");
}

$data = json_decode($response, true);
$fb_id = $data['id'] ?? '';
if (!$fb_id) {
    die("Không Lấy Được ID");
}

if (!preg_match('/^(61|1000)/', $fb_id)) {
    die("ID $fb_id Không Hợp Lệ - Phải Bắt Đầu Bằng 61 Hoặc 1000");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://smmlikevip.com/api/v2");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'key' => 'fdded2a450ab33c38764ceb5a3c971ef5368cc708bc5a09e9e450a7118df65d2',
    'action' => 'add',
    'service' => '68861',
    'link' => $fb_id,
    'quantity' => 100
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$result = curl_exec($ch);
curl_close($ch);
$webhook_url = "https://vphuc.online/api/webhook.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['result' => $result]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);

echo "OK - Đã Gửi Kết Quả Về Webhook";
?>
