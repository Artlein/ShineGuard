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
     * @param string|int $nodeId Firebase Node Name (recursive) or SL-ID
     */
    public static function sendBulkCommand($nodeId, $hwMode, $brightness) {
        require_once __DIR__ . '/../../firebase_config.php';

        // --- BROADCAST LOGIC ---
        if ($nodeId === 'all') {
            $nodes = \FirebaseConfig::getAllIoTDevices();
            $success = true;
            foreach ($nodes as $name => $cfg) {
                if (!self::sendSingleNodeCommand($name, $hwMode, $brightness)) {
                    $success = false;
                }
            }
            return $success;
        }

        // --- RESOLVE NODE NAME ---
        // If nodeId is numeric (MySQL ID), find the node_name
        if (is_numeric($nodeId)) {
            // SL-001 -> SG-NODE2 mapping
            $fbNode = 'SG-NODE2'; // Default fallback
            if ($nodeId == 1) $fbNode = 'SG-NODE2';
            elseif ($nodeId == 2) $fbNode = 'SG-NODE3';
            // Actually, we should use the mapping from config
            $nodeId = $fbNode;
        }

        return self::sendSingleNodeCommand($nodeId, $hwMode, $brightness);
    }

    /**
     * Internal helper for unified hardware object structure
     */
    private static function sendSingleNodeCommand($fbNode, $hwMode, $brightness) {
        $payload = [
            'mode' => (int)$hwMode,
            'targetBrightness' => (int)$brightness,
            'commandTimestamp' => round(microtime(true) * 1000)
        ];

        // This uses the dynamic config resolver to target the correct Firebase URL
        // Endpoint 'control' maps to '/Control.json'
        return \FirebaseConfig::writeData('control', $payload, $fbNode);
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
