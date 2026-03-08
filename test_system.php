<?php

echo "<!DOCTYPE html><html><head><title>System Test</title>";
echo "<style>body{font-family:sans-serif;padding:40px;background:#f5f5f5;}";
echo ".ok{color:#22c55e;font-weight:700;} .fail{color:#ef4444;font-weight:700;}</style></head><body>";
echo "<h1>🧪 Shine Guard Hulo - System Test</h1>";

echo "<h2>1. Database Connection</h2>";
require_once 'dbconnect.php';
if ($conn->connect_error) {
    echo "<p class='fail'>❌ FAILED: " . $conn->connect_error . "</p>";
} else {
    echo "<p class='ok'>✅ Connected to database successfully!</p>";
}

echo "<h2>2. Database Tables</h2>";
$tables = ['users', 'streetlights', 'sensor_data', 'alerts', 'cctv_cameras', 'maintenance_logs'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p class='ok'>✅ $table: {$row['count']} records</p>";
    } else {
        echo "<p class='fail'>❌ $table: Table not found!</p>";
    }
}

echo "<h2>3. User Accounts</h2>";
$users = $conn->query("SELECT username, email, role FROM users");
if ($users && $users->num_rows > 0) {
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th style='padding:8px;'>Username</th><th style='padding:8px;'>Email</th><th style='padding:8px;'>Role</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr><td style='padding:8px;'>{$user['username']}</td><td style='padding:8px;'>{$user['email']}</td><td style='padding:8px;'>{$user['role']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='fail'>❌ No users found! Run reset_password.php</p>";
}

echo "<h2>4. File Structure</h2>";
$required_files = [
    'login.php' => 'Login page',
    'dashboard.php' => 'Dashboard',
    'streetlights.php' => 'Streetlights',
    'cctv.php' => 'CCTV',
    'logout.php' => 'Logout',
    'includes/header.php' => 'Header include',
    'includes/sidebar.php' => 'Sidebar include',
    'assets/style.css' => 'CSS stylesheet',
    'api/get_time.php' => 'Time API',
    'api/get_light_details.php' => 'Light details API'
];

foreach ($required_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p class='ok'>✅ $desc: $file</p>";
    } else {
        echo "<p class='fail'>❌ $desc: $file NOT FOUND!</p>";
    }
}

echo "<h2>5. Streetlight Data</h2>";
$lights = $conn->query("SELECT COUNT(*) as total, 
                        SUM(CASE WHEN power_state = 'ON' THEN 1 ELSE 0 END) as online,
                        SUM(CASE WHEN power_state = 'OFF' THEN 1 ELSE 0 END) as offline
                        FROM streetlights");
if ($lights) {
    $data = $lights->fetch_assoc();
    echo "<p class='ok'>✅ Total streetlights: {$data['total']}</p>";
    echo "<p class='ok'>✅ Online: {$data['online']}</p>";
    echo "<p>Offline: {$data['offline']}</p>";
} else {
    echo "<p class='fail'>❌ Cannot fetch streetlight data</p>";
}

echo "<h2>6. Session Capability</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p class='ok'>✅ Sessions are working</p>";
} else {
    echo "<p class='fail'>❌ Sessions not working</p>";
}

echo "<hr><h2>🎯 Summary</h2>";
echo "<p><strong>If all tests show ✅, your system is ready!</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Go to <a href='reset_password.php'>reset_password.php</a> to create admin user</li>";
echo "<li>Go to <a href='login.php'>login.php</a> to login</li>";
echo "<li>Delete test_system.php after testing</li>";
echo "</ol>";

echo "<p style='margin-top:30px;'><a href='login.php' style='background:#22c55e;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:700;'>→ Go to Login Page</a></p>";

echo "</body></html>";

$conn->close();
?>
