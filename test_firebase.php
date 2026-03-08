<?php
require_once 'dbconnect.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Firebase Data Test</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .test-box { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        button:hover { background: #059669; }
    </style>
</head>
<body>
    <h1>🔥 Firebase Data Test Page</h1>
    
    <div class="test-box">
        <h2>Test 1: Database Connection</h2>
        <?php if ($conn): ?>
            <div class="status success">✓ Database connected successfully</div>
        <?php else: ?>
            <div class="status error">✗ Database connection failed</div>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>Test 2: Check SL-001 exists</h2>
        <?php
        $check = $conn->query("SELECT light_id, node_name FROM streetlights WHERE node_name = 'SL-001' LIMIT 1");
        if ($check && $row = $check->fetch_assoc()):
        ?>
            <div class="status success">✓ SL-001 found (ID: <?php echo $row['light_id']; ?>)</div>
        <?php else: ?>
            <div class="status error">✗ SL-001 not found in database</div>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>Test 3: Latest Sensor Data</h2>
        <?php
        $data_query = "SELECT * FROM sensor_data 
                       WHERE light_id = (SELECT light_id FROM streetlights WHERE node_name = 'SL-001' LIMIT 1)
                       ORDER BY timestamp DESC LIMIT 1";
        $data_result = $conn->query($data_query);
        if ($data_result && $data = $data_result->fetch_assoc()):
        ?>
            <div class="status success">✓ Data found</div>
            <pre><?php print_r($data); ?></pre>
        <?php else: ?>
            <div class="status error">✗ No sensor data found. Run firebase_sync_silent.php first!</div>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>Test 4: API Endpoint Test</h2>
        <button onclick="testAPI()">Click to Test API</button>
        <div id="api-result" style="margin-top: 15px;"></div>
    </div>

    <div class="test-box">
        <h2>Test 5: Firebase Sync</h2>
        <button onclick="runSync()">Click to Run Firebase Sync</button>
        <div id="sync-result" style="margin-top: 15px;"></div>
    </div>

    <div class="test-box">
        <h2>Quick Actions</h2>
        <p>
            <a href="dashboard.php" style="color: #10b981; font-weight: 600;">← Back to Dashboard</a><br>
            <a href="firebase_sync_silent.php" target="_blank" style="color: #3b82f6; font-weight: 600;">→ Run Sync Manually</a><br>
            <a href="firebase_dashboard.php" style="color: #ef4444; font-weight: 600;">→ Firebase Full Dashboard</a>
        </p>
    </div>

    <script>
    function testAPI() {
        const resultDiv = document.getElementById('api-result');
        resultDiv.innerHTML = '<div style="color: #64748b;">Loading...</div>';
        
        fetch('api/get_firebase_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="status success">✓ API Working!</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                        <div style="margin-top: 10px;">
                            🌡️ Temperature: ${data.temperature}°C<br>
                            💡 Brightness: ${data.brightness} lux<br>
                            ⚡ Voltage: ${data.voltage}V<br>
                            💧 Humidity: ${data.humidity}%
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="status error">✗ ${data.message}</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="status error">✗ Error: ${error.message}</div>`;
            });
    }

    function runSync() {
        const resultDiv = document.getElementById('sync-result');
        resultDiv.innerHTML = '<div style="color: #64748b;">Syncing with Firebase...</div>';
        
        fetch('firebase_sync_silent.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="status success">✓ Sync Complete!</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                        <p style="margin-top: 10px;">Now click "Test API" above to see the data!</p>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="status error">✗ Sync Failed</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="status error">✗ Error: ${error.message}</div>`;
            });
    }
    </script>
</body>
</html>
