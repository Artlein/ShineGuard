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
}
