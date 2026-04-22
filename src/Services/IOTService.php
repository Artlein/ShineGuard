<?php
/**
 * SHINEGUARD IOT SERVICE
 * Responsibility: Streetlight Management, Sensor Data, and MQTT Status
 */

namespace ShineGuard\Services;

class IOTService {
    
    public static function getStreetlightSummary($conn) {
        $result = $conn->query("SELECT status, communication_protocol, COUNT(*) as count FROM streetlights GROUP BY status, communication_protocol");
        $summary = [];
        while($row = $result->fetch_assoc()) {
            $summary[] = $row;
        }
        return $summary;
    }

    public static function getLatestTelemetry($conn, $light_id) {
        $stmt = $conn->prepare("SELECT * FROM sensor_data WHERE light_id = ? ORDER BY timestamp DESC LIMIT 1");
        $stmt->bind_param("i", $light_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $data;
    }

    public static function updateNodeStatus($conn, $light_id, $status) {
        $stmt = $conn->prepare("UPDATE streetlights SET status = ?, last_updated = NOW() WHERE light_id = ?");
        $stmt->bind_param("si", $status, $light_id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Sends a command to a specific IoT node via its respective Firebase instance.
     */
    public static function sendBulkCommand($nodeId, $mode, $brightness) {
        // Log the command attempt
        $firebaseUpdate = [
            'mode' => $mode,
            'targetBrightness' => $brightness,
            'commandTimestamp' => round(microtime(true) * 1000)
        ];

        // This uses the dynamic config resolver to target the correct Firebase URL
        return \FirebaseConfig::writeData('control', $firebaseUpdate, $nodeId);
    }

    /**
     * Map a dimming percentage to a human-readable label and color scheme.
     */
    public static function getDimmingLabel($level) {
        $level = intval($level);
        if ($level <= 25)
            return ['label' => '🍃 Energy Saver', 'color' => '#3b82f6', 'bg' => '#eff6ff'];
        if ($level <= 50)
            return ['label' => '🌓 Medium', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'];
        if ($level <= 75)
            return ['label' => '🌔 High', 'color' => '#f59e0b', 'bg' => '#fffbeb'];
        return ['label' => '🌕 Full', 'color' => '#10b981', 'bg' => '#ecfdf5'];
    }
}
