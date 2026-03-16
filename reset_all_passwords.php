<?php
require 'dbconnect.php';

$new_password = 'Admin@123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash = ?, failed_attempts = 0, last_failed_attempt = NULL, lockout_until = NULL");
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "SUCCESS: All passwords reset to: $new_password<br>";
    echo "SUCCESS: All accounts unlocked and failed attempt counters reset.<br>";
} else {
    echo "ERROR: " . $conn->error;
}

$stmt->close();
$conn->query("DELETE FROM login_attempts");
echo "SUCCESS: Cleared IP lockouts.<br>";

unlink(__FILE__);
?>
