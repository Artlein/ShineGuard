<?php
require_once 'dbconnect.php';
$res = $conn->query("SELECT @@session.time_zone as tz, NOW() as now");
$row = $res->fetch_assoc();
echo "MySQL Session Timezone: " . $row['tz'] . "\n";
echo "MySQL NOW(): " . $row['now'] . "\n";
echo "PHP date(): " . date('Y-m-d H:i:s') . "\n";
?>
