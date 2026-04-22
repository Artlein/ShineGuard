<?php
/**
 * SHINEGUARD MAINTENANCE SERVICE
 * Responsibility: Workforce MTTR Analytics, PM Triggering, and Inventory Governance
 */

namespace ShineGuard\Services;

class MaintenanceService {

    /**
     * Calculates Mean Time to Repair (MTTR) for a technician or the whole system
     */
    public static function calculateMTTR($conn, $user_id = null) {
        $filter = $user_id ? " AND user_id = $user_id" : "";
        
        $sql = "SELECT AVG(completion_time) as mttr 
                FROM maintenance_logs 
                WHERE status = 'Completed' AND completion_time IS NOT NULL $filter";
        
        $res = $conn->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return round($row['mttr'] ?? 0, 1);
        }
        return 0;
    }

    /**
     * Identifies nodes that require Preventative Maintenance (PM)
     */
    public static function getPendingPM($conn) {
        // PM Logic: Light is > 1 year old OR > 4000 runtime hours
        $sql = "SELECT * FROM streetlights 
                WHERE ((installed_at <= DATE_SUB(NOW(), INTERVAL 1 YEAR)) 
                OR (runtime_hours >= 4000))
                AND status NOT IN ('Maintenance', 'Inactive')";
        
        $result = $conn->query($sql);
        $nodes = [];
        while($row = $result->fetch_assoc()) {
            $nodes[] = $row;
        }
        return $nodes;
    }

    /**
     * Retrieves current inventory status including low-stock alerts
     */
    public static function getInventoryStatus($conn) {
        $result = $conn->query("SELECT *, (quantity <= min_stock_level) as low_stock FROM inventory_stock ORDER BY category, part_name");
        $items = [];
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Logs an asset lifecycle event
     */
    /**
     * Logs an asset lifecycle event
     */
    public static function logAssetEvent($conn, $light_id, $event_type, $details) {
        AuditService::logActivity($conn, IdentityService::getUserId(), "ASSET_$event_type", "Node #$light_id: $details");
    }

    // ── FORENSIC ARCHIVE & RECOVERY (FAR) ENGINE ──

    /**
     * Generates a complete database snapshot with a forensic SHA-256 hash
     */
    public static function generateForensicSnapshot($conn, $admin_id, $notes = '') {
        // 1. Security Check: Must be recently authorized via SBA
        if (!IdentityService::isRecentlyAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized', 'message' => 'Secure session expired. Please re-verify.'];
        }

        $backupDir = __DIR__ . '/../../backups/';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

        $timestamp = date('Ymd_His');
        $filename = "SG_FORENSIC_SNAP_{$timestamp}_" . bin2hex(random_bytes(4)) . ".sql";
        $filePath = $backupDir . $filename;

        // 2. Identify Environment and Binary
        $is_aws = file_exists('/var/www/html/ShineGuard');
        $mysqldump = $is_aws ? 'mysqldump' : '/Applications/XAMPP/xamppfiles/bin/mysqldump';
        
        // 3. Prepare Credentials
        $db_host = $_ENV['DB_HOST'] ?? 'localhost';
        $db_name = $_ENV['DB_NAME'] ?? 'Hulo';
        $db_user = $is_aws ? ($_ENV['DB_USER_AWS'] ?? 'shineguard') : ($_ENV['DB_USER'] ?? 'root');
        $db_pass = $is_aws ? ($_ENV['DB_PASS_AWS'] ?? 'ShineGuard2026') : ($_ENV['DB_PASS'] ?? '');

        $passArg = $db_pass ? "-p" . escapeshellarg($db_pass) : "";
        $command = "{$mysqldump} -h {$db_host} -u {$db_user} {$passArg} {$db_name} > " . escapeshellarg($filePath);
        
        // 4. Execute Dump
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return ['success' => false, 'error' => 'dump_failed', 'message' => 'Database export failed. Check server permissions.'];
        }

        // 5. Generate Forensic Hash
        $hash = hash_file('sha256', $filePath);
        $fileSize = filesize($filePath);

        // 6. Register in Database
        $stmt = $conn->prepare("INSERT INTO backup_registry (filename, snapshot_hash, filesize, notes, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $filename, $hash, $fileSize, $notes, $admin_id);
        
        if ($stmt->execute()) {
            AuditService::logActivity($conn, $admin_id, 'FAR_SNAPSHOT_CREATED', "Forensic snapshot generated: $filename (Hash: " . substr($hash, 0, 8) . "...)");
            return ['success' => true, 'filename' => $filename, 'hash' => $hash];
        }

        return ['success' => false, 'error' => 'registry_failed', 'message' => 'Failed to log snapshot in registry.'];
    }

    /**
     * Lists all forensic snapshots with real-time integrity status
     */
    public static function getForensicSnapshots($conn) {
        $sql = "SELECT r.*, u.full_name as admin_name 
                FROM backup_registry r 
                LEFT JOIN users u ON r.created_by = u.user_id 
                ORDER BY r.created_at DESC";
        
        $result = $conn->query($sql);
        $snapshots = [];
        
        if (!$result) {
            error_log("FAR ERROR: Database query failed in getForensicSnapshots: " . $conn->error);
            return [];
        }

        $backupDir = __DIR__ . '/../../backups/';

        while ($row = $result->fetch_assoc()) {
            $filePath = $backupDir . $row['filename'];
            $row['exists'] = file_exists($filePath);
            
            if ($row['exists']) {
                $currentHash = hash_file('sha256', $filePath);
                $row['integrity_valid'] = hash_equals($row['snapshot_hash'] ?? '', $currentHash ?: '');
            } else {
                $row['integrity_valid'] = false;
            }
            
            $snapshots[] = $row;
        }
        return $snapshots;
    }

    /**
     * Restores the system to a previous forensic state
     */
    public static function restoreForensicSnapshot($conn, $snapshot_id, $admin_id) {
        // 1. Security Check: Must be recently authorized via SBA
        if (!IdentityService::isRecentlyAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized', 'message' => 'Secure session required for restoration.'];
        }

        // 2. Fetch Snapshot Metadata
        $stmt = $conn->prepare("SELECT * FROM backup_registry WHERE id = ?");
        $stmt->bind_param("i", $snapshot_id);
        $stmt->execute();
        $snapshot = $stmt->get_result()->fetch_assoc();
        
        if (!$snapshot) {
            return ['success' => false, 'error' => 'not_found', 'message' => 'Snapshot not found in registry.'];
        }

        $backupDir = __DIR__ . '/../../backups/';
        $filePath = $backupDir . $snapshot['filename'];

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'file_missing', 'message' => 'Physical backup file was deleted from disk.'];
        }

        // 3. FORENSIC VALIDATION: Check for tampering
        $currentHash = hash_file('sha256', $filePath);
        if (!hash_equals($snapshot['snapshot_hash'], $currentHash)) {
            AuditService::logActivity($conn, $admin_id, 'FAR_TAMPER_DETECTED', "CRITICAL: Attempted restore from tampered file: {$snapshot['filename']}");
            return ['success' => false, 'error' => 'tampered', 'message' => 'FORENSIC ALERT: Snapshot hash mismatch. File has been tampered with.'];
        }

        // 4. Identify Environment and Binary
        $is_aws = file_exists('/var/www/html/ShineGuard');
        $mysql = $is_aws ? 'mysql' : '/Applications/XAMPP/xamppfiles/bin/mysql';
        
        $db_host = $_ENV['DB_HOST'] ?? 'localhost';
        $db_name = $_ENV['DB_NAME'] ?? 'Hulo';
        $db_user = $is_aws ? ($_ENV['DB_USER_AWS'] ?? 'shineguard') : ($_ENV['DB_USER'] ?? 'root');
        $db_pass = $is_aws ? ($_ENV['DB_PASS_AWS'] ?? 'ShineGuard2026') : ($_ENV['DB_PASS'] ?? '');

        // 5. Execute Restore
        $passArg = $db_pass ? "-p" . escapeshellarg($db_pass) : "";
        $command = "{$mysql} -h {$db_host} -u {$db_user} {$passArg} {$db_name} < " . escapeshellarg($filePath);
        
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            AuditService::logActivity($conn, $admin_id, 'FAR_RESTORE_COMPLETE', "System restored to state: {$snapshot['filename']}");
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'restore_failed', 'message' => 'SQL import failed. Check database logs.'];
    }

    /**
     * Deletes a forensic snapshot permanently
     */
    public static function deleteForensicSnapshot($conn, $snapshot_id, $admin_id) {
        if (!IdentityService::isRecentlyAuthorized()) {
            return ['success' => false, 'error' => 'not_authorized', 'message' => 'Secure session required.'];
        }

        $stmt = $conn->prepare("SELECT filename FROM backup_registry WHERE id = ?");
        $stmt->bind_param("i", $snapshot_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res) {
            $filePath = __DIR__ . '/../../backups/' . $res['filename'];
            if (file_exists($filePath)) unlink($filePath);
            
            $del = $conn->prepare("DELETE FROM backup_registry WHERE id = ?");
            $del->bind_param("i", $snapshot_id);
            $del->execute();
            
            AuditService::logActivity($conn, $admin_id, 'FAR_SNAPSHOT_DELETED', "Snapshot deleted: {$res['filename']}");
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'not_found'];
    }
}
