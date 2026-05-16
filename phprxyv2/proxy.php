<?php
// Load config
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo 'Config file not found!';
    exit;
}
$config = require $configFile;

class ShadowProxy {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function fetch($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL');
            return;
        }
        
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            $this->error('Only HTTP/HTTPS allowed');
            return;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => $this->config['user_agent'] ?? 'Mozilla/5.0',
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
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            $this->error('Connection failed: ' . $error);
            return;
        }
        
        http_response_code($httpCode);
        if ($contentType) {
            header('Content-Type: ' . $contentType);
        }
        
        echo $body;
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

// Handle request
if (isset($_GET['url'])) {
    $proxy = new ShadowProxy($config);
    $proxy->fetch($_GET['url']);
} else {
    header('Location: index.php');
    exit;
}
