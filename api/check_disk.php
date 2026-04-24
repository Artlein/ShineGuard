<?php
require_once '../dbconnect.php';
requireLogin(['System Admin']);

$dir = dirname(__DIR__) . '/snapshots';
echo "<h1>Disk Audit</h1>";
echo "Directory: <b>$dir</b><br>";
echo "Exists: " . (is_dir($dir) ? "YES" : "NO") . "<br>";
echo "Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br>";

$files = scandir($dir);
echo "<h3>Files Found (" . count($files) . ")</h3>";
echo "<pre>";
foreach($files as $f) {
    if($f === '.' || $f === '..') continue;
    $p = "$dir/$f";
    echo "$f (" . filesize($p) . " bytes) - " . date("Y-m-d H:i:s", filemtime($p)) . "\n";
}
echo "</pre>";
?>
