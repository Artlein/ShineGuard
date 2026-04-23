<?php
require_once __DIR__ . '/../dbconnect.php';

echo "CONNECTED TO DB: " . (isset($conn) ? "YES" : "NO") . "\n";
$res = $conn->query("SELECT COUNT(*) as c FROM alerts");
$row = $res->fetch_assoc();
echo "TOTAL ALERTS IN MYSQL: " . $row['c'] . "\n";

$res = $conn->query("SELECT database()");
$db = $res->fetch_row();
echo "CURRENT DATABASE: " . $db[0] . "\n";
