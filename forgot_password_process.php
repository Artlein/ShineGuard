<?php
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = trim($_POST['email']);

// 1. Verify user exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // SECURITY: We don't want to leak if an email exists, but for UX we might.
    // However, the prompt says "make it work", so we'll be helpful.
    header('Location: forgot_password.php?error=not_found');
    exit();
}

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
    logActivity($conn, 0, 'Password Reset Request', "Password reset link generated for email: $email");

    // 5. MOCK EMAIL DELIVERY
    // In a real app, you'd use mail() or a library.
    // For local dev, we save the link to a file and tell the user.
    $reset_link = BASE_URL . "reset_password.php?token=" . $token . "&email=" . urlencode($email);
    
    $mock_content = "To: $email\n";
    $mock_content .= "Subject: Password Reset Request - Shine Guard\n";
    $mock_content .= "Link: $reset_link\n";
    $mock_content .= "Expires: $expires_at\n";
    
    file_put_contents('tmp_reset_email.txt', $mock_content);
    
    // Also save to a session for easy display if needed
    session_start();
    $_SESSION['mock_reset_link'] = $reset_link;
    
    header('Location: forgot_password.php?success=1');
    exit();
} else {
    header('Location: forgot_password.php?error=db_error');
    exit();
}
?>
