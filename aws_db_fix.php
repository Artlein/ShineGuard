<?php
/**
 * SHINEGUARD AWS DATABASE REPAIR SCRIPT
 * This script synchronizes the AWS MySQL schema with the new Admin Recovery features.
 */
require_once 'dbconnect.php';

echo "<h2>🛡️ ShineGuard AWS Database Synchronization</h2>";

try {
    // 1. Check if 'status' column exists
    $result = $conn->query("SHOW COLUMNS FROM `password_resets` LIKE 'status'");
    
    if ($result->num_rows == 0) {
        echo "Updating 'password_resets' table...<br>";
        
        $sql = "ALTER TABLE password_resets 
                ADD COLUMN status ENUM('Pending', 'Fulfilled', 'Dismissed') DEFAULT 'Pending',
                ADD COLUMN admin_notes TEXT AFTER status";
                
        if ($conn->query($sql)) {
            echo "✅ <strong>Success:</strong> Security tracking columns added to AWS Database.<br>";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "ℹ️ Database is already up to date.<br>";
    }
    
    echo "<br><p style='color: green; font-weight: bold;'>The dashboard crash should now be resolved! You can delete this file for security.</p>";
    echo "<a href='dashboard.php' style='display:inline-block; padding:10px 20px; background:#10b981; color:white; border-radius:8px; text-decoration:none; margin-top:20px;'>Return to Dashboard</a>";

} catch (Exception $e) {
    echo "❌ <strong>Error during sync:</strong> " . $e->getMessage() . "<br>";
    echo "Please ensure dbconnect.php has the correct AWS credentials.";
}
