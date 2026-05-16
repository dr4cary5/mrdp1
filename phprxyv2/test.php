<?php
echo "<h1>PHP Works!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// تست config
if (file_exists('config.php')) {
    echo "<p>✅ config.php found</p>";
} else {
    echo "<p>❌ config.php NOT found</p>";
}

// تست proxy.php
if (file_exists('proxy.php')) {
    echo "<p>✅ proxy.php found</p>";
} else {
    echo "<p>❌ proxy.php NOT found</p>";
}

// تست curl
if (function_exists('curl_init')) {
    echo "<p>✅ curl installed</p>";
} else {
    echo "<p>❌ curl NOT installed</p>";
}

// تست یه URL ساده
$ch = curl_init('https://httpbin.org/ip');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p>❌ curl error: " . htmlspecialchars($error) . "</p>";
} else {
    echo "<p>✅ curl works! Response: " . htmlspecialchars($result) . "</p>";
}
?>
