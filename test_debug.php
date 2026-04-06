<?php
require_once 'dbconnect.php';
echo "<h1>Debug Info</h1>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "CSRF Token: " . ($_SESSION['csrf_token'] ?? 'MISSING') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'LOGGED OUT') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NONE') . "<br>";
echo "isRecentlyAuthorized: " . (isRecentlyAuthorized() ? 'YES' : 'NO') . "<br>";
?>
