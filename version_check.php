<?php
echo "Current Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Script Last Modified: " . date('Y-m-d H:i:s', filemtime(__FILE__)) . "\n";
if (file_exists('.git/HEAD')) {
    echo "Git HEAD: " . file_get_contents('.git/HEAD') . "\n";
} else {
    echo "Git directory not found or inaccessible.\n";
}
?>
