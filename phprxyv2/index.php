<?php
header('ngrok-skip-browser-warning: true');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShadowX Proxy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="logo">🕶️</div>
        <h1>ShadowX Proxy</h1>
        <p class="tagline">In shadow we trust, in freedom we surf</p>
        
        <form method="get" action="proxy.php" class="url-form">
            <input type="text" name="url" placeholder="Enter URL (e.g. google.com)" autofocus autocomplete="off">
            <button type="submit">Go</button>
        </form>
        
        <div class="features">
            <span class="feature">⚡ Fast</span>
            <span class="feature">🔒 HTTPS</span>
            <span class="feature">🌍 Any Site</span>
            <span class="feature">🎭 Stealth</span>
        </div>
    </div>
</body>
</html>
