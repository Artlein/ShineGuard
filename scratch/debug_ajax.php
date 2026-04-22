<?php
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'System Admin';
$_GET['action'] = 'list_snapshots';

require_once 'maintenance_actions.php';
