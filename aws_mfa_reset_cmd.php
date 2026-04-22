<?php
require_once 'dbconnect.php';
session_start();

// SECURITY: Only allow User ID 1 (Admin) to trigger this via an authenticated session
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    die("Unauthorized Access Sentinel: Reset denied.");
}

$stmt = $conn->prepare("UPDATE users SET mfa_enabled = 0, mfa_secret = NULL WHERE user_id = ?");
$user_id = 1;
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo "<h1>✅ AWS MFA RESET SUCCESSFUL</h1>";
    echo "<p>MFA has been wiped for User #1. Please go to your <b>Security Tab</b> now and Scan the Fresh QR code.</p>";
} else {
    echo "<h1>❌ RESET FAILED</h1>" . $conn->error;
}
$stmt->close();
?>
