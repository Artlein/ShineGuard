<?php
// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ShineGuard AWS Diagnostic</h1>";

echo "<h2>1. Database Connection</h2>";
require_once __DIR__ . '/../dbconnect.php';
if ($conn) {
    echo "✅ Connected to: " . $conn->host_info . "<br>";
} else {
    echo "❌ Database connection failed!<br>";
}

echo "<h2>2. Table Verification</h2>";
$tables = ['user_devices', 'password_resets', 'alerts', 'users'];
foreach ($tables as $t) {
    $res = $conn->query("DESC $t");
    if ($res) {
        echo "✅ Table '$t' exists.<br>";
        if ($t === 'user_devices') {
            $has_ack = false;
            while ($row = $res->fetch_assoc()) {
                if ($row['Field'] === 'is_acknowledged') $has_ack = true;
            }
            if ($has_ack) echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Column 'is_acknowledged' found.<br>";
            else echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Column 'is_acknowledged' MISSING!<br>";
        }
    } else {
        echo "❌ Table '$t' MISSING!<br>";
    }
}

echo "<h2>3. Library/Service Health</h2>";
$files = [
    'src/Services/SecurityService.php',
    'includes/header.php',
    'includes/sidebar.php'
];
foreach ($files as $f) {
    $path = __DIR__ . '/../' . $f;
    if (file_exists($path)) {
        echo "✅ File '$f' exists.<br>";
        // Attempt to include or check
    } else {
        echo "❌ File '$f' MISSING!<br>";
    }
}

echo "<h2>4. Session State</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NONE') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NONE') . "<br>";

?>
