<?php
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    header('Location: forgot_password.php');
    exit();
}

checkCsrf();
checkRateLimit('pw_reset_request', 3, 10); // Max 3 requests per 10 mins per IP

$email = trim($_POST['email']);

// 1. Verify user exists and get full name for personalization
$stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // SECURITY: DEFENSIVE ENUMERATION PROTECTION
    usleep(rand(100000, 300000)); 
    header('Location: forgot_password.php?success=1');
    exit();
}

$user_data = $res->fetch_assoc();
$full_name = $user_data['full_name'];

// 2. Clear any old tokens for this email
$conn->query("DELETE FROM password_resets WHERE email = '$email'");

// 3. Generate token
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 4. Store token
$stmt = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $token_hash, $expires_at);

if ($stmt->execute()) {
    // Log the request
    logActivity($conn, null, 'Password Reset Request', "Password reset link generated for email: $email");

    // 5. CORPORATE EMAIL DISPATCH
    require_once 'src/Services/MailService.php';
    $reset_link = BASE_URL . "reset_password.php?token=" . $token . "&email=" . urlencode($email);
    
    $result = \ShineGuard\Services\MailService::sendPasswordReset($email, $full_name, $reset_link);
    
    // Even if mail fails (e.g. key missing), we show success to the user for security.
    // The developer can check Audit Logs or Error Logs for failures.
    if (!$result['success']) {
        error_log("Mail Error: " . $result['error']);
        logActivity($conn, null, 'Mail Delivery Error', "Failed to send recovery email to $email. Error: " . $result['error']);
    }

    header('Location: forgot_password.php?success=1');
    exit();
} else {
    header('Location: forgot_password.php?error=db_error');
    exit();
}
?>
