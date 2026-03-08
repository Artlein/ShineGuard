<?php

require_once '../dbconnect.php';
requireLoginApi();

header('Content-Type: text/plain');
echo date('M d, Y H:i:s');
?>
