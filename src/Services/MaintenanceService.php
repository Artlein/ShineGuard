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
                WHERE (installed_at <= DATE_SUB(NOW(), INTERVAL 1 YEAR)) 
                OR (runtime_hours >= 4000)
                AND status != 'Maintenance'";
        
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
    public static function logAssetEvent($conn, $light_id, $event_type, $details) {
        // Placeholder for an asset_timeline table if we create it later
        // For now, we reuse the AuditService
        AuditService::logActivity($conn, 0, "ASSET_$event_type", "Node #$light_id: $details");
    }
}
