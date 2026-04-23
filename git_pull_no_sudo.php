<?php
/**
 * 🚀 EMERGENCY GIT PULL (NO SUDO)
 * Try this if the primary sync fails due to password prompt.
 */
header('Content-Type: text/plain');
echo "Attempting to pull latest changes from main branch...\n";
$output = shell_exec("git pull origin main 2>&1");
echo "OUTPUT:\n" . $output;
?>
