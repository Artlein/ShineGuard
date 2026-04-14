<?php
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['token']) || empty($_POST['password'])) {
    header('Location: login.php');
    exit();
}

checkCsrf();

$token = $_POST['token'];
$email = $_POST['email'];
$password = $_POST['password'];

// 1. Verify token again
$token_hash = hash('sha256', $token);
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token_hash = ? LIMIT 1");
$stmt->bind_param("ss", $email, $token_hash);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $data = $res->fetch_assoc();
    if (strtotime($data['expires_at']) > time()) {
        // 2. Token is valid. Update password.
        $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $new_hash, $email);
        
        if ($update_stmt->execute()) {
            // 3. Delete token
            $conn->query("DELETE FROM password_resets WHERE email = '$email'");
            
            // 4. Log activity
            // Find user_id
            $u_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $u_stmt->bind_param("s", $email);
            $u_stmt->execute();
            $user_id = $u_stmt->get_result()->fetch_assoc()['user_id'];
            
            logActivity($conn, $user_id, 'Password Reset', 'Password changed via recovery flow');
            
            header('Location: login.php?success=password_reset');
            exit();
        } else {
             // System error
             header('Location: forgot_password.php?error=db_error');
             exit();
        }
    }
}

// If we fall through, it's an invalid/expired token attempt
header('Location: login.php?error=invalid_token');
exit();
?>
