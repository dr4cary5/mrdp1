<?php
header('ngrok-skip-browser-warning: true');
echo "<h1>PHP Works!</h1>";
echo "<p>Version: " . phpversion() . "</p>";
echo "<p>Curl: " . (function_exists('curl_init') ? '✅' : '❌') . "</p>";

$ch = curl_init('https://httpbin.org/ip');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$r = curl_exec($ch);
$e = curl_error($ch);
curl_close($ch);

echo "<p>Test fetch: " . ($e ? '❌ '.$e : '✅ '.$r) . "</p>";
