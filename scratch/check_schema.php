<?php
require_once 'dbconnect.php';
$res = $conn->query("DESCRIBE user_devices");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
