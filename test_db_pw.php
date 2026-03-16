<?php
require 'dbconnect.php';
echo "DB Connection Success.<br>";

if ($conn->connect_error) {
    die("DB error: " . $conn->connect_error);
}

$res = $conn->query("SELECT user_id, email, password_hash, is_active, failed_attempts, lockout_until FROM users");
if (!$res) {
    echo "Error querying users: " . $conn->error;
} else {
    while($row = $res->fetch_assoc()) {
        echo "<b>User ID:</b> {$row['user_id']} | <b>Email:</b> {$row['email']} | <b>Active:</b> {$row['is_active']} | <b>Failed Attempts:</b> {$row['failed_attempts']} | <b>Lockout:</b> {$row['lockout_until']}<br>";
        echo "<b>Hash:</b> {$row['password_hash']}<br>";
        echo "Verify 'password123': " . (password_verify('password123', $row['password_hash']) ? 'YES' : 'NO') . "<br>";
        echo "Verify 'admin123': " . (password_verify('admin123', $row['password_hash']) ? 'YES' : 'NO') . "<br><br>";
    }
}

$ip = '::1'; // Assuming curl localhost
$locked = isIpLockedOut($conn, $ip);
echo "Are we (::1) locked out? " . ($locked ? 'YES' : 'NO') . "<br>";

$ip2 = '127.0.0.1';
$locked2 = isIpLockedOut($conn, $ip2);
echo "Are we (127.0.0.1) locked out? " . ($locked2 ? 'YES' : 'NO') . "<br>";

unlink(__FILE__); // Self-delete
?>
