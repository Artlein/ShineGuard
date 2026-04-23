<?php

namespace ShineGuard\Controllers;

use ShineGuard\Services\IOTService;
use ShineGuard\Services\MaintenanceService;

/**
 * StreetlightController
 * 
 * Handles the business logic for the Streetlight Management module.
 * Decouples request handling and data preparation from the View.
 */
class StreetlightController {
    
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Entry point for the "Index" page of streetlights.
     * Fetches all necessary data for the UI.
     */
    public function index() {
        // Fetch all streetlights for the map and list
        $query = "SELECT * FROM streetlights ORDER BY light_id ASC";
        $result = $this->conn->query($query);
        $streetlights = [];
        while ($row = $result->fetch_assoc()) {
            $streetlights[] = $row;
        }

        // Fetch Stats
        $stats = $this->conn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance,
            SUM(CASE WHEN power_state = 'ON' THEN 1 ELSE 0 END) as power_on
            FROM streetlights")->fetch_assoc();

        // Fetch MTTR and PM stats from Service layer
        $mttr = MaintenanceService::calculateMTTR($this->conn);
        $pending_pm = MaintenanceService::getPendingPM($this->conn);

        // Fetch User Security Context
        $user_id = intval($_SESSION['user_id']);
        $ctx_stmt = $this->conn->prepare("SELECT mfa_enabled FROM users WHERE user_id = ?");
        $ctx_stmt->bind_param("i", $user_id);
        $ctx_stmt->execute();
        $user_ctx = $ctx_stmt->get_result()->fetch_assoc();
        $ctx_stmt->close();

        return [
            'streetlights' => $streetlights,
            'stats' => $stats,
            'mttr' => $mttr,
            'pending_pm' => $pending_pm,
            'user' => $user_ctx
        ];
    }

    /**
     * Handles all POST actions for the streetlight module.
     */
    public function handleAction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // 1. Password Verification (AJAX or Form)
        if (isset($_POST['action']) && $_POST['action'] === 'verify_password') {
            $this->verifyPassword();
        }

        // 2. Bulk Action Handling
        if (isset($_POST['bulk_action'])) {
            $this->handleBulkAction();
        }

        // 3. Add Streetlight
        if (isset($_POST['action']) && $_POST['action'] === 'add_streetlight') {
            $this->addStreetlight();
        }

        // 4. Remove Streetlight
        if (isset($_POST['action']) && $_POST['action'] === 'remove_streetlight') {
            $this->removeStreetlight();
        }

        // 5. Individual Light Toggle
        if (isset($_POST['light_id']) && isset($_POST['action']) && $_POST['action'] === 'toggle') {
            $this->handleToggleAction();
        }
    }

    private function handleToggleAction() {
        checkCsrf();
        if (!canDo('control_streetlights')) $this->redirect('error=unauthorized');

        $light_id = intval($_POST['light_id']);
        $power = $_POST['power_state'] === 'ON' ? 'OFF' : 'ON';
        $admin_password = $_POST['admin_password'] ?? '';

        // Verification
        if (!isRecentlyAuthorized()) {
            // Reject sentinel value - actual password required
            if (empty($admin_password) || $admin_password === '__session_authorized__') {
                $this->redirect('error=invalid_password');
            }
            if (!$this->checkAdminPassword($admin_password)) {
                $this->redirect('error=invalid_password');
            }
            setRecentlyAuthorized();
        }

        // Update MySQL
        $stmt = $this->conn->prepare("UPDATE streetlights SET power_state = ? WHERE light_id = ?");
        $stmt->bind_param("si", $power, $light_id);
        $stmt->execute();

        // Cloud Sync (Demo targets node 001/SG-NODE2)
        $nodeQuery = $this->conn->prepare("SELECT node_name, dimming_level FROM streetlights WHERE light_id = ?");
        $nodeQuery->bind_param("i", $light_id);
        $nodeQuery->execute();
        $nodeData = $nodeQuery->get_result()->fetch_assoc();

        if ($nodeData && $nodeData['node_name'] === 'SL-001') {
            // Fix: Manual (1) for forced state, 0 brightness for OFF
            $hwMode = 1;
            $hwBright = ($power === 'ON' ? $nodeData['dimming_level'] : 0);
            IOTService::sendBulkCommand(1, $hwMode, $hwBright);
        }

        logActivity($this->conn, $_SESSION['user_id'], 'Light Control', "Toggled light #$light_id to $power");
        $this->redirect('success=toggle_success');
    }

    private function verifyPassword() {
        checkCsrf();
        $admin_password = $_POST['admin_password'] ?? '';
        $user_id = $_SESSION['user_id'];

        // If already authorized, we can accept the sentinel or just return success
        if (isRecentlyAuthorized()) {
            echo json_encode(['success' => true]);
            exit();
        }

        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();

        if ($user_data && password_verify($admin_password, $user_data['password_hash'])) {
            setRecentlyAuthorized();
            logActivity($this->conn, $user_id, 'Elevated Access', 'User successfully elevated session access for Streetlight Control');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }

    private function handleBulkAction() {
        checkCsrf();
        if (!canDo('control_streetlights')) {
            $this->redirect('error=unauthorized');
        }

        $action = $_POST['bulk_action'];
        $dimming = intval($_POST['dimming_level'] ?? 70);
        $range_val = $_POST['bulk_range'] ?? '';

        // Verification check if not recently authorized
        if (!isRecentlyAuthorized()) {
            $admin_password = $_POST['bulk_admin_password'] ?? '';
            if (!$this->checkAdminPassword($admin_password)) {
                $this->redirect('error=invalid_password');
            }
            setRecentlyAuthorized();
        }

        // Build Where Clause
        $where_clause = $this->buildWhereClause($range_val);
        $scope_desc = empty($where_clause) ? "all streetlights" : "selected range";

        if ($action === 'ON') {
            $stmt = $this->conn->prepare("UPDATE streetlights SET power_state = 'ON', dimming_level = ?" . $where_clause);
            $stmt->bind_param("i", $dimming);
            $stmt->execute();
            // Bulk ON -> Manual (1) at $dimming
            IOTService::sendBulkCommand('all', 1, $dimming);
            logActivity($this->conn, $_SESSION['user_id'], 'Bulk Control', "Turned $scope_desc ON at $dimming%");
        } else {
            $stmt = $this->conn->prepare("UPDATE streetlights SET power_state = 'OFF'" . $where_clause);
            $stmt->execute();
            // Bulk OFF -> Manual (1) at 0
            IOTService::sendBulkCommand('all', 1, 0);
            logActivity($this->conn, $_SESSION['user_id'], 'Bulk Control', "Turned $scope_desc OFF");
        }

        $this->redirect('success=bulk_success');
    }

    private function addStreetlight() {
        if (!canDo('manage_streetlights')) $this->redirect('error=unauthorized');
        checkCsrf();

        $node_name = sanitize($_POST['node_name']);
        $location  = sanitize($_POST['location']);
        $lat       = floatval($_POST['latitude']);
        $lng       = floatval($_POST['longitude']);
        $install_date = !empty($_POST['installation_date']) ? $_POST['installation_date'] : date('Y-m-d');

        $stmt = $this->conn->prepare("INSERT INTO streetlights (node_name, location, latitude, longitude, installation_date, status, power_state, dimming_level) VALUES (?, ?, ?, ?, ?, 'Active', 'OFF', 70)");
        $stmt->bind_param("ssdds", $node_name, $location, $lat, $lng, $install_date);

        if ($stmt->execute()) {
            logActivity($this->conn, $_SESSION['user_id'], 'Add Streetlight', "Added new streetlight: $node_name");
            $this->redirect('success=add_success');
        } else {
            $this->redirect('error=db_error');
        }
    }

    private function removeStreetlight() {
        if (!canDo('manage_streetlights')) $this->redirect('error=unauthorized');
        checkCsrf();

        // ── ZERO TRUST: Action Stepping (MFA Required for Destructive Action) ──
        $user_id = $_SESSION['user_id'];
        $stmt = $this->conn->prepare("SELECT mfa_secret, mfa_enabled FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();

        if ($user_data && $user_data['mfa_enabled']) {
            $mfa_code = $_POST['mfa_code'] ?? '';
            if (!\ShineGuard\Services\TOTPService::verifyCode($user_data['mfa_secret'], $mfa_code)) {
                $this->redirect('error=invalid_mfa&action=remove&light_id=' . $_POST['light_id']);
            }
        }

        $light_id = intval($_POST['light_id']);
        $stmt = $this->conn->prepare("DELETE FROM streetlights WHERE light_id = ?");
        $stmt->bind_param("i", $light_id);

        if ($stmt->execute()) {
            logActivity($this->conn, $_SESSION['user_id'], 'Remove Streetlight', "Removed streetlight ID: $light_id (MFA Authenticated)");
            $this->redirect('success=remove_success');
        } else {
            $this->redirect('error=db_error');
        }
    }

    // --- Helper Methods ---

    private function checkAdminPassword($password) {
        $user_id = $_SESSION['user_id'];
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        return ($user_data && password_verify($password, $user_data['password_hash']));
    }

    private function buildWhereClause($range_val) {
        if (empty($range_val)) return "";
        
        if (strpos($range_val, '-') !== false) {
            $parts = explode('-', $range_val);
            $start = intval(trim($parts[0]));
            $end = intval(trim($parts[1]));
            return " WHERE light_id BETWEEN $start AND $end";
        } else {
            $raw_ids = preg_split('/[\s,]+/', trim($range_val));
            $clean_ids = array_filter(array_map('intval', $raw_ids));
            if (!empty($clean_ids)) {
                return " WHERE light_id IN (" . implode(',', $clean_ids) . ")";
            }
        }
        return "";
    }

    private function redirect($query) {
        header("Location: streetlights.php?$query");
        exit();
    }
}
