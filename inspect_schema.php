<?php
require_once 'dbconnect.php';

echo "<h1>📋 DATABASE SCHEMA INSPECTION</h1>";

function inspectTable($conn, $tableName) {
    echo "<h3>Table: $tableName</h3>";
    $res = $conn->query("DESCRIBE $tableName");
    if (!$res) {
        echo "❌ Table '$tableName' not found or error: " . $conn->error;
        return;
    }
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($r = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$r['Field']}</td>
            <td>{$r['Type']}</td>
            <td>{$r['Null']}</td>
            <td>{$r['Key']}</td>
            <td>{$r['Default']}</td>
            <td>{$r['Extra']}</td>
        </tr>";
    }
    echo "</table>";
}

inspectTable($conn, 'sensor_data');
inspectTable($conn, 'streetlights');

echo "<h3>🏁 Inspection Complete</h3>";
?>
