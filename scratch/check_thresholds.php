<?php
require_once 'dbconnect.php';
$output = "";
$res = $conn->query("SELECT * FROM system_config WHERE config_key LIKE '%threshold%'");
while($row = $res->fetch_assoc()) {
    $output .= $row['config_key'] . ": " . $row['config_value'] . "\n";
}
file_put_contents('scratch/thresholds.txt', $output);
?>
