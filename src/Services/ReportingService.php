<?php
/**
 * SHINEGUARD REPORTING SERVICE
 * Responsibility: KPI Aggregation, Insight Generation, and PDF Archival
 */

namespace ShineGuard\Services;

class ReportingService {
    
    /**
     * Gets high-level system performance metrics
     */
    public static function getSystemKPIs($conn, $start_date, $end_date) {
        $stats = $conn->query("SELECT 
            (SELECT COUNT(*) FROM streetlights) as total_lights,
            (SELECT COUNT(*) FROM streetlights WHERE status = 'Active') as active_lights,
            (SELECT COUNT(*) FROM alerts WHERE created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59') as total_alerts,
            (SELECT COUNT(*) FROM alerts WHERE severity = 'High' AND created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59') as critical_alerts,
            (SELECT AVG(completion_time) FROM maintenance_logs WHERE maintenance_date BETWEEN '$start_date' AND '$end_date' AND status = 'Completed') as avg_resolution_time
        ")->fetch_assoc();
        
        return $stats;
    }

    /**
     * Generates human-friendly "Insights" based on raw data
     */
    public static function generateInsights($stats) {
        $insights = [];
        
        $active_pct = ($stats['total_lights'] > 0) ? ($stats['active_lights'] / $stats['total_lights'] * 100) : 0;
        
        if ($active_pct >= 95) {
            $insights[] = "System uptime is optimal ({$active_pct}%). All city sectors are currently illuminated.";
        } else {
            $insights[] = "Infrastructure alert: Illumination rate dropped to {$active_pct}%. Maintenance dispatch recommended.";
        }
        
        if ($stats['critical_alerts'] > 5) {
            $insights[] = "Critical security event frequency EXCEEDS threshold. Forensic audit required.";
        } else {
            $insights[] = "Security events are within nominal parameters for this period.";
        }
        
        return $insights;
    }

    /**
     * Archives a generated report metadata in the DB
     */
    public static function archiveReport($conn, $name, $type, $range, $filename, $user_id) {
        $file_path = "exports/reports/" . $filename;
        $file_hash = file_exists($file_path) ? hash_file('sha256', $file_path) : null;
        
        $stmt = $conn->prepare("INSERT INTO report_archive (report_name, report_type, period_range, generated_by, filename, file_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $name, $type, $range, $user_id, $filename, $file_hash);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }

    /**
     * Retrieves the list of archived reports
     */
    public static function getArchive($conn, $limit = 10) {
        $result = $conn->query("SELECT ra.*, u.username as generator 
                               FROM report_archive ra 
                               LEFT JOIN users u ON ra.generated_by = u.user_id 
                               ORDER BY ra.generated_at DESC LIMIT $limit");
        $archive = [];
        while($row = $result->fetch_assoc()) {
            $archive[] = $row;
        }
        return $archive;
    }
}
