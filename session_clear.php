<?php
require_once 'dbconnect.php';
session_unset();
session_destroy();
header('Location: login.php?msg=session_cleared');
exit();
?>
