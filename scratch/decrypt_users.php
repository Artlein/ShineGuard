<?php
require_once 'dbconnect.php';
require_once 'src/Services/SecurityService.php';

use ShineGuard\Services\SecurityService;

$res = $conn->query("SELECT * FROM users");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . "\n";
    echo "Username: " . SecurityService::decrypt($row['username']) . "\n";
    echo "Full Name: " . SecurityService::decrypt($row['full_name']) . "\n";
    echo "Role: " . SecurityService::decrypt($row['role']) . "\n";
    echo "--------------------\n";
}
?>
