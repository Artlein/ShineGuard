<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once 'dbconnect.php';
    logActivity($conn, $_SESSION['user_id'], 'Logout', 'User logged out');
    $conn->close();
}

session_unset();
session_destroy();

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/");
}

header('Location: login.php?logout=success');
exit();
?>
