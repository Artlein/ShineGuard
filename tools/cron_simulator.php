<?php
/**
 * SHINEGUARD CRON SIMULATOR
 * Demonstrates Pillar 7: Automated Scheduling
 */

require_once '../dbconnect.php';
requireLogin('System Admin');

use ShineGuard\Services\ReportingService;

$schedules = $conn->query("SELECT * FROM report_schedules WHERE is_active = 1");
$processed = 0;

while ($sch = $schedules->fetch_assoc()) {
    // In a real system, we would check if next_run < NOW()
    // Here we just simulate a manual trigger for the demonstration
    
    $start = date('Y-m-d', strtotime('-7 days'));
    $end = date('Y-m-d');
    $filename = "auto_weekly_audit_" . date('Ymd_His') . ".pdf";
    
    // Simulate File Creation
    $dummy_content = "This is a simulated automated report for " . $sch['report_type'];
    file_put_contents("../exports/reports/" . $filename, $dummy_content);
    
    // Archive it
    ReportingService::archiveReport($conn, "Automated " . $sch['report_type'], $sch['report_type'], "$start to $end", $filename, 0); // 0 = System
    
    // Update last run
    $conn->query("UPDATE report_schedules SET last_run = NOW() WHERE schedule_id = " . $sch['schedule_id']);
    
    $processed++;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cron Engine | ShineGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0c0d10; color: #fff; padding: 40px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #18181b; border: 1px solid #27272a; padding: 40px; border-radius: 24px; text-align: center; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .success-icon { font-size: 4rem; margin-bottom: 20px; animation: scale 0.5s ease-out; }
        h1 { margin: 0; font-weight: 900; }
        p { color: #a1a1aa; margin: 10px 0 20px; }
        .btn { background: #10b981; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-block; transition: transform 0.2s; }
        .btn:hover { transform: scale(1.05); }
        @keyframes scale { 0% { transform: scale(0); } 80% { transform: scale(1.2); } 100% { transform: scale(1); } }
    </style>
</head>
<body>
    <div class="card">
        <div class="success-icon">⚙️</div>
        <h1>Automation Engine Initialized</h1>
        <p>Processed <strong><?php echo $processed; ?></strong> scheduled reporting tasks. Your official archives have been synchronized with the latest system snapshots.</p>
        <a href="../reports.php" class="btn">View Reporting Hub</a>
    </div>
</body>
</html>
