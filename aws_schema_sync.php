<?php
require_once 'dbconnect.php';

// Safe environment to run schema sync
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>AWS Schema Sync Tool</h2>";

function addColumnIfNotExists($conn, $table, $column, $def) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows === 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $def")) {
            echo "<p style='color:green'>Added column <b>$column</b> to $table.</p>";
            return true;
        } else {
            echo "<p style='color:red'>Error adding $column: " . $conn->error . "</p>";
            return false;
        }
    } else {
        echo "<p style='color:gray'>Column <b>$column</b> already exists in $table.</p>";
        return true;
    }
}

// Ensure users table features exist
$user_cols = [
    ['mfa_secret', "VARCHAR(255) NULL"],
    ['mfa_enabled', "TINYINT(1) NOT NULL DEFAULT 0"],
    ['email_blind_index', "VARCHAR(64) NULL"],
    ['username_blind_index', "VARCHAR(64) NULL"],
    ['failed_attempts', "INT NOT NULL DEFAULT 0"],
    ['last_failed_attempt', "DATETIME NULL"],
    ['lockout_until', "DATETIME NULL"],
    ['phone', "VARCHAR(50) NULL"]
];

foreach ($user_cols as $c) {
    addColumnIfNotExists($conn, 'users', $c[0], $c[1]);
}

// Generate Blind Indexes for existing users and encrypt fields to zero-trust
echo "<h3>Updating User Encryption & Indexes...</h3>";
$users = $conn->query("SELECT user_id, email, username, full_name, role FROM users");
if ($users) {
    while ($u = $users->fetch_assoc()) {
        $upd = $conn->prepare("UPDATE users SET email_blind_index = ? WHERE user_id = ?");
        $idx = \ShineGuard\Services\SecurityService::generateBlindIndex($u['email']);
        $upd->bind_param("si", $idx, $u['user_id']);
        $upd->execute();
        $upd->close();
    }
    echo "<p style='color:green'>Security Sync Complete!</p>";
} else {
    echo "<p style='color:red'>Error syncing users: " . $conn->error . "</p>";
}

echo "<br><hr><p>Sync finished successfully. <a href='login.php'>Return to Login</a></p>";
?>
