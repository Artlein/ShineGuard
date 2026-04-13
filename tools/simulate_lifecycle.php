<?php
/**
 * SHINEGUARD LIFECYCLE SIMULATOR
 * Responsibility: Simulates time passing and hardware wear-and-tear
 */

require_once '../dbconnect.php';
requireLogin('System Admin');

// 1. Aging the hardware
$sql = "UPDATE streetlights SET 
        runtime_hours = runtime_hours + 500,
        installed_at = DATE_SUB(installed_at, INTERVAL 1 MONTH)";
$conn->query($sql);

// 2. Randomly triggering a "Low Stock" event
$conn->query("UPDATE inventory_stock SET quantity = 2 WHERE part_number = 'LUX-HP-01'");

logActivity($conn, $_SESSION['user_id'], 'Lifecycle Simulated', 'Aged all hardware by 500 hours and decreased sensor stock for demonstration.');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Lifecycle Simulator | ShineGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0c0d10; color: #fff; padding: 40px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #18181b; border: 1px solid #27272a; padding: 40px; border-radius: 24px; text-align: center; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        h1 { margin: 0; font-weight: 900; }
        p { color: #a1a1aa; margin: 10px 0 20px; }
        .btn { background: #8b5cf6; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-block; transition: transform 0.2s; }
        .btn:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⏳</div>
        <h1>Time Accelerated</h1>
        <p>All city infrastructure has been aged by 500 operational hours. Low-stock conditions have been triggered for maintenance inventory sensors.</p>
        <a href="../work_orders.php" class="btn">View Maintenance Command</a>
    </div>
</body>
</html>
