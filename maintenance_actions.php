<?php
/**
 * FAR CONTROLLER - Forensic Archive & Recovery
 * Handles AJAX requests for system snapshots and restorations.
 */
require_once 'dbconnect.php';
requireLoginApi('System Admin');

use ShineGuard\Services\MaintenanceService;
use ShineGuard\Services\IdentityService;

// Ensure JSON response
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── SESSION STABILITY CHECK ──
if (!isset($_SESSION['user_id'])) {
    error_log("FAR ACTION REJECTED: Session lost during action $action. IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'session_lost', 'message' => 'Identity mismatch: Session lost. Please re-login.']);
    exit();
}
error_log("FAR ACTION INITIATED: User ID " . $_SESSION['user_id'] . " executing action $action");

// Diagnostic: Verify schema existence
$tableCheck = $conn->query("SHOW TABLES LIKE 'backup_registry'");
if ($tableCheck->num_rows === 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Forensic Registry table missing. Please run database migration.']);
    exit();
}

try {
    switch ($action) {
        case 'generate_snapshot':
            checkCsrf();
            $mfa = $_POST['mfa_code'] ?? '';
            if (!IdentityService::verifyActionMfa($conn, $mfa)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'mfa_failed', 'message' => 'Valid MFA code required for forensic operations.']);
                exit();
            }
            $notes = sanitize($_POST['notes'] ?? 'Manual Forensic Snapshot');
            $result = MaintenanceService::generateForensicSnapshot($conn, IdentityService::getUserId(), $notes);
            echo json_encode($result);
            break;

        case 'restore_snapshot':
            checkCsrf();
            $mfa = $_POST['mfa_code'] ?? '';
            if (!IdentityService::verifyActionMfa($conn, $mfa)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'mfa_failed', 'message' => 'Valid MFA code required for restoration.']);
                exit();
            }
            $id = (int)($_POST['id'] ?? 0);
            $result = MaintenanceService::restoreForensicSnapshot($conn, $id, IdentityService::getUserId());
            echo json_encode($result);
            break;

        case 'delete_snapshot':
            checkCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $result = MaintenanceService::deleteForensicSnapshot($conn, $id, IdentityService::getUserId());
            echo json_encode($result);
            break;

        case 'list_snapshots':
            // No CSRF strictly required for read-only listing in this context
            $snapshots = MaintenanceService::getForensicSnapshots($conn);
            echo json_encode(['success' => true, 'snapshots' => $snapshots]);
            break;

        case 'check_sba':
            echo json_encode(['authorized' => IdentityService::isRecentlyAuthorized()]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'invalid_action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()]);
}
