<?php
header('ngrok-skip-browser-warning: true');

if (!isset($_GET['url'])) {
    header('Location: index.php');
    exit;
}

$url = $_GET['url'];
if (!preg_match('/^https?:\/\//i', $url)) {
    $url = 'https://' . $url;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_ENCODING => '',
]);

$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo "<h1>Error</h1><p>$error</p><a href='index.php'>Back</a>";
    exit;
}

http_response_code($code);
if ($type) header('Content-Type: ' . $type);
echo $body;
