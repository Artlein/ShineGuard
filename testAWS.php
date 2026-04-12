<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
require 'dbconnect.php';
$conn->query("SELECT 1 FROM activity_logs LIMIT 1") or die($conn->error);
echo "SUCCESS";
