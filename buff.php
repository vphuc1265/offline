<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('GIST_TOKEN', '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');
define('GIST_ID', '6eaf110c6672bd20fc0920fe4ce03fc8');

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
        'User-Agent: Mozilla/5.0 (GitHub Action)'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

$key = getenv('INPUT_KEY');
$link = getenv('INPUT_LINK');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://id.traodoisub.com/api.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['link' => $link]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
$fb_id = $data['id'] ?? '';

if (!$fb_id) {
    saveToGist("[</>] Không Lấy Được ID Từ Link");
    die();
}

if (!preg_match('/^(61|1000)/', $fb_id)) {
    saveToGist("[</>] ID $fb_id Không Hợp Lệ");
    die();
}

// 2. Gọi SMM API
$smm_data = [
    'key' => 'fdded2a450ab33c38764ceb5a3c971ef5368cc708bc5a09e9e450a7118df65d2',
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
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    saveToGist("</>] Lỗi API HTTP $httpCode");
    die();
}

$smm_result = json_decode($result, true);

if (isset($smm_result['status']) && $smm_result['status'] == 'success') {
    $output = "[</>] Tăng Follow Facebook Thành Công - Vui Lòng Chờ Xử Lý";
} else {
    $error_msg = $smm_result['msg'] ?? $smm_result['error'] ?? '[</>] Lỗi Không Xác Định';
    if (preg_match('/Vui lòng đợi thời gian còn lại: (.+)/', $error_msg, $matches)) {
        $output = "[</>] Quay Lại Sau " . $matches[1];
    } else {
        $output = "$error_msg";
    }
}

saveToGist($output);
echo "OK";
?>
