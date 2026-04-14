<?php
require_once '../dbconnect.php';
requireLoginApi();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $password = $_POST['admin_password'] ?? '';
    $action = $_POST['action'] ?? 'authorize';
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && password_verify($password, $result['password_hash'])) {
        if ($action === 'revoke') {
            revokeAuthorization();
            logActivity($conn, $user_id, 'Access Revocation', 'Elevated session access revoked manually');
        } elseif ($action === 'verify') {
            // One-time verification: Success but don't call setAuthorized()
            logActivity($conn, $user_id, 'Action Verification', 'User verified identity for a one-time secure action');
        } else {
            setAuthorized();
            logActivity($conn, $user_id, 'Elevated Access', 'User successfully elevated session access (SBA)');
        }
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid administrator password.']);
    }
    exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Legacy support or quick revoke without password if called directly
    // But for the new flow, we use POST + password.
    revokeAuthorization();
    echo json_encode(['success' => true]);
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
