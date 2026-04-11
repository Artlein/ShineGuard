<?php
require_once '../dbconnect.php';
requireLoginApi();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    revokeAuthorization();
    logActivity($conn, $_SESSION['user_id'], 'Access Revocation', 'Elevated session access revoked');
    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
}
?>
