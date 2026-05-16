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
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo "<h1>Error</h1><p>$error</p><a href='index.php'>Back</a>";
    exit;
}

// Rewrite HTML
if ($type && strpos($type, 'text/html') !== false) {
    $body = rewriteUrls($body, $finalUrl);
}

http_response_code($code);
if ($type) header('Content-Type: ' . $type);
echo $body;


function rewriteUrls($html, $baseUrl) {
    $proxyBase = 'proxy.php?url=';
    $baseParts = parse_url($baseUrl);
    $baseHost = $baseParts['scheme'] . '://' . $baseParts['host'];
    
    // src, href, action
    $html = preg_replace_callback(
        '/(src|href|action)=["\'](.*?)["\']/i',
        function($m) use ($proxyBase, $baseHost, $baseParts) {
            $attr = $m[1];
            $link = $m[2];
            
            // Skip non-http links
            if (preg_match('/^(data:|javascript:|#|mailto:|tel:)/i', $link)) {
                return $m[0];
            }
            
            // Absolute URL
            if (preg_match('/^https?:\/\//i', $link)) {
                return $attr . '="' . $proxyBase . urlencode($link) . '"';
            }
            
            // Protocol-relative
            if (preg_match('/^\/\//', $link)) {
                return $attr . '="' . $proxyBase . urlencode($baseParts['scheme'] . ':' . $link) . '"';
            }
            
            // Root-relative
            if (preg_match('/^\//', $link)) {
                return $attr . '="' . $proxyBase . urlencode($baseHost . $link) . '"';
            }
            
            // Relative
            $dir = dirname($baseParts['path'] ?? '/');
            return $attr . '="' . $proxyBase . urlencode($baseHost . $dir . '/' . $link) . '"';
        },
        $html
    );
    
    // url() in CSS
    $html = preg_replace_callback(
        '/url\(["\']?(.*?)["\']?\)/i',
        function($m) use ($proxyBase, $baseHost, $baseParts) {
            $link = $m[1];
            if (preg_match('/^(data:|#)/i', $link)) return $m[0];
            if (preg_match('/^https?:\/\//i', $link)) {
                return 'url(' . $proxyBase . urlencode($link) . ')';
            }
            if (preg_match('/^\//', $link)) {
                return 'url(' . $proxyBase . urlencode($baseHost . $link) . ')';
            }
            return $m[0];
        },
        $html
    );
    
    return $html;
}
