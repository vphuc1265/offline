<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('GIST_TOKEN', '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');
define('GIST_ID', '6eaf110c6672bd20fc0920fe4ce03fc8');

$test_content = 'Test from GitHub Action at ' . date('Y-m-d H:i:s');

$data = json_encode([
    'files' => [
        'result.txt' => [
            'content' => $test_content
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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlInfo = curl_getinfo($ch);
curl_close($ch);

$log = "=== TEST GIST ===\n";
$log .= "Time: " . date('Y-m-d H:i:s') . "\n";
$log .= "HTTP Code: $httpCode\n";
$log .= "CURL Error: $curlError\n";
$log .= "Response: $response\n";
$log .= "CURL Info: " . print_r($curlInfo, true) . "\n";
$log .= "================\n";

file_put_contents('gist_test.log', $log, FILE_APPEND);

if ($httpCode == 200) {
    die("✅ GIST TEST SUCCESS - Đã ghi: $test_content");
} else {
    die("❌ GIST TEST FAILED - Xem file gist_test.log");
}
?>
