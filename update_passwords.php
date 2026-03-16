<?php
require 'dbconnect.php';

$users = [
    'admin@hulo.gov.ph' => 'ShineGuard2025!',
    'aizadmin@hulo.gov.ph' => 'ShineGuard2026!',
    'rajhiadmin@hulo.gov.ph' => 'ShineGuard2027!',
    'arvinadmin@hulo.gov.ph' => 'ShineGuard2028!'
];

foreach ($users as $email => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    if ($stmt->execute()) {
        echo "Updated $email to $password<br>";
    } else {
        echo "Error updating $email: " . $conn->error . "<br>";
    }
    $stmt->close();
}

$conn->query("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL, lockout_until = NULL");
$conn->query("DELETE FROM login_attempts");

echo "Lockouts cleared.<br>";
unlink(__FILE__);
?>
