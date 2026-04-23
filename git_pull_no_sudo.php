<?php
/**
 * 🚀 EMERGENCY GIT PULL (NO SUDO)
 * Try this if the primary sync fails due to password prompt.
 */
header('Content-Type: text/plain');
echo "Attempting to pull latest changes from main branch...\n";
// Fix dubious ownership issue
shell_exec("git config --global --add safe.directory /var/www/html/ShineGuard");
$output = shell_exec("git pull origin main 2>&1");
echo "OUTPUT:\n" . $output;
?>
