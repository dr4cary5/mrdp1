<?php
//最简单的版本 - Simplest version
$url = $_GET['url'] ?? '';

if (empty($url)) {
    header('Location: index.php');
    exit;
}

// اضافه کردن پروتکل
if (!preg_match('#^https?://#i', $url)) {
    $url = 'https://' . $url;
}

// Fetch
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo "<h1>Error</h1><p>$error</p>";
    exit;
}

http_response_code($httpCode);
if ($contentType) header("Content-Type: $contentType");
echo $body;
