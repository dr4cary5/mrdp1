<?php
return [
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    'max_file_size' => 50 * 1024 * 1024,
    'timeout' => 30,
    'follow_redirects' => true,
    'max_redirects' => 5,
    'strip_headers' => ['x-frame-options', 'content-security-policy', 'strict-transport-security'],
    'blacklist' => [],
    'allowed_ports' => [80, 443, 8080, 8443],
];
