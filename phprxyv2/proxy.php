<?php
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) { http_response_code(500); echo 'Config not found!'; exit; }
$config = require $configFile;

class ShadowProxy {
    private $config;
    private $proxyBase;
    
    public function __construct($config) {
        $this->config = $config;
        $this->proxyBase = $_SERVER['PHP_SELF'] . '?url=';
    }
    
    public function fetch($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) { $this->error('Invalid URL'); return; }
        
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) { $this->error('Only HTTP/HTTPS allowed'); return; }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => $this->config['user_agent'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);
        
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) { $this->error('Connection failed: ' . $error); return; }
        
        // Rewrite body
        if ($contentType && strpos($contentType, 'text/html') !== false) {
            $body = $this->rewriteContent($body, $url, $finalUrl);
        }
        
        http_response_code($httpCode);
        if ($contentType) header('Content-Type: ' . $contentType);
        echo $body;
    }
    
    private function rewriteContent($html, $originalUrl, $finalUrl) {
        $baseHost = parse_url($finalUrl, PHP_URL_HOST);
        $proxyUrl = $this->proxyBase;
        
        // Rewrite src, href, action
        $html = preg_replace_callback(
            '/(src|href|action)=["\'](.*?)["\']/i',
            function($matches) use ($baseHost, $proxyUrl, $finalUrl) {
                $attr = $matches[1];
                $url = $matches[2];
                
                if (preg_match('/^(data:|javascript:|#|mailto:|tel:)/i', $url)) return $matches[0];
                
                if (preg_match('/^https?:\/\//i', $url)) {
                    return $attr . '="' . $proxyUrl . urlencode($url) . '"';
                } elseif (preg_match('/^\//', $url)) {
                    $scheme = parse_url($finalUrl, PHP_URL_SCHEME);
                    $host = parse_url($finalUrl, PHP_URL_HOST);
                    return $attr . '="' . $proxyUrl . urlencode($scheme . '://' . $host . $url) . '"';
                } else {
                    $base = dirname($finalUrl);
                    return $attr . '="' . $proxyUrl . urlencode($base . '/' . $url) . '"';
                }
            },
            $html
        );
        
        // Rewrite form actions
        $html = preg_replace_callback(
            '/(<form[^>]+action)=["\'](.*?)["\']/i',
            function($matches) use ($proxyUrl) {
                if (preg_match('/^(data:|javascript:|#)/i', $matches[2])) return $matches[0];
                $newUrl = $proxyUrl . urlencode($matches[2]);
                return $matches[1] . '="' . $newUrl . '"';
            },
            $html
        );
        
        // Inject base tag
        if (stripos($html, '<base') === false) {
            $html = str_ireplace('<head>', '<head><base href="' . $proxyUrl . urlencode($finalUrl) . '">', $html);
        }
        
        return $html;
    }
    
    private function error($message) {
        http_response_code(502);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>Error</title></head>';
        echo '<body style="background:#0a0a0a;color:#e0e0e0;font-family:sans-serif;text-align:center;padding:50px;">';
        echo '<h1 style="color:#6e00ff;">⚠️ Proxy Error</h1>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '<a href="index.php" style="color:#8a2be2;">← Back</a>';
        echo '</body></html>';
        exit;
    }
}

if (isset($_GET['url'])) {
    $proxy = new ShadowProxy($config);
    $proxy->fetch($_GET['url']);
} else {
    header('Location: index.php');
    exit;
}
