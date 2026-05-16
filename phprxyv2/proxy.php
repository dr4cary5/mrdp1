<?php
require_once 'config.php';
$config = require 'config.php';

class ShadowProxy {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function fetch($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL');
            return;
        }
        
        // Parse URL
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            $this->error('Only HTTP/HTTPS allowed');
            return;
        }
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $this->config['follow_redirects'],
            CURLOPT_MAXREDIRS => $this->config['max_redirects'],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_USERAGENT => $this->config['user_agent'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '', // gzip, deflate, brotli
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Cache-Control: no-cache',
            ],
            CURLOPT_HEADERFUNCTION => function($curl, $header) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                
                if (count($header) < 2) return $len;
                
                $name = strtolower(trim($header[0]));
                if (in_array($name, $this->config['strip_headers'])) {
                    return $len;
                }
                
                header(trim($header[0]) . ': ' . trim($header[1]));
                return $len;
            },
        ]);
        
        // Execute
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        if (curl_errno($ch)) {
            $this->error('Connection failed: ' . curl_error($ch));
            curl_close($ch);
            return;
        }
        
        curl_close($ch);
        
        http_response_code($httpCode);
        if ($contentType) {
            header('Content-Type: ' . $contentType);
        }
        
        // Rewrite URLs in HTML
        if (strpos($contentType, 'text/html') !== false) {
            $body = $this->rewriteUrls($body, $url);
        }
        
        echo $body;
    }
    
    private function rewriteUrls($html, $baseUrl) {
        // Rewrite relative URLs to go through proxy
        $proxyUrl = $_SERVER['PHP_SELF'] . '?url=';
        
        // Rewrite src, href, action attributes
        $html = preg_replace_callback(
            '/(src|href|action)=["\'](.*?)["\']/i',
            function($matches) use ($baseUrl, $proxyUrl) {
                $url = $matches[2];
                
                // Skip data: and javascript: URLs
                if (preg_match('/^(data:|javascript:|#|mailto:)/i', $url)) {
                    return $matches[0];
                }
                
                // Convert relative to absolute
                if (!preg_match('/^https?:\/\//i', $url)) {
                    $url = rtrim(dirname($baseUrl), '/') . '/' . ltrim($url, '/');
                }
                
                return $matches[1] . '="' . $proxyUrl . urlencode($url) . '"';
            },
            $html
        );
        
        return $html;
    }
    
    private function error($message) {
        http_response_code(502);
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="background:#1a1a1a;color:#e0e0e0;padding:20px;text-align:center;font-family:sans-serif;">';
        echo '<h2 style="color:#6e00ff;">⚠️ Error</h2>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '<a href="index.php" style="color:#8a2be2;">← Back</a>';
        echo '</div>';
    }
}

// Handle request
if (isset($_GET['url'])) {
    $proxy = new ShadowProxy($config);
    $proxy->fetch($_GET['url']);
}
