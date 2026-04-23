<?php
/**
 * FAR CONTROLLER - Forensic Archive & Recovery
 * Handles AJAX requests for system seeds and restorations.
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
        case 'generate_seed':
            checkCsrf();
            $password = $_POST['password'] ?? '';
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$user_data || !password_verify($password, $user_data['password_hash'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'auth_failed', 'message' => 'Admin password required for forensic operations.']);
                exit();
            }

            // SUCCESS: Lock in the session for 5 minutes (SBA)
            IdentityService::setRecentlyAuthorized();

            $notes = sanitize($_POST['notes'] ?? 'Manual Forensic Seed');
            $result = MaintenanceService::generateForensicSeed($conn, $user_id, $notes);
            echo json_encode($result);
            break;

        case 'restore_seed':
            checkCsrf();
            $password = $_POST['password'] ?? '';
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$user_data || !password_verify($password, $user_data['password_hash'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'auth_failed', 'message' => 'Admin password required for restoration.']);
                exit();
            }

            // SUCCESS: Lock in the session for 5 minutes (SBA)
            IdentityService::setRecentlyAuthorized();

            $id = (int)($_POST['id'] ?? 0);
            $result = MaintenanceService::restoreForensicSeed($conn, $id, $user_id);
            echo json_encode($result);
            break;

        case 'delete_seed':
            checkCsrf();
            $password = $_POST['password'] ?? $_POST['admin_password'] ?? '';
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$user_data || !password_verify($password, $user_data['password_hash'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'auth_failed', 'message' => 'Admin password required for record deletion.']);
                exit();
            }

            IdentityService::setRecentlyAuthorized();

            $id = (int)($_POST['id'] ?? 0);
            $result = MaintenanceService::deleteForensicSeed($conn, $id, $user_id);
            echo json_encode($result);
            break;

        case 'list_seeds':
            // No CSRF strictly required for read-only listing in this context
            $seeds = MaintenanceService::getForensicSeeds($conn);
            echo json_encode(['success' => true, 'seeds' => $seeds]);
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
