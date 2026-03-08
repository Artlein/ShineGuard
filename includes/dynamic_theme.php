<?php

if (!function_exists('getConfig')) {
    include_once __DIR__ . '/../config_loader.php';
}

$theme_color = getConfig($conn, 'theme_color', '#10b981');

function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

$rgb = hexToRgb($theme_color);
?>
<style>
:root {
    --theme-color: <?php echo $theme_color; ?>;
    --theme-color-rgb: <?php echo $rgb['r']; ?>, <?php echo $rgb['g']; ?>, <?php echo $rgb['b']; ?>;
    --theme-hover: color-mix(in srgb, <?php echo $theme_color; ?> 80%, black);
    --theme-light: color-mix(in srgb, <?php echo $theme_color; ?> 10%, white);
}

.btn.primary,
button.btn.primary {
    background: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

.btn.primary:hover,
button.btn.primary:hover {
    background: var(--theme-hover) !important;
    border-color: var(--theme-hover) !important;
}

.badge.ok {
    background: var(--theme-light);
    color: var(--theme-color);
    border: 1px solid var(--theme-color);
}

.sidebar a.active {
    background: var(--theme-light);
    color: var(--theme-color);
    border-left: 3px solid var(--theme-color);
}

a.link-primary {
    color: var(--theme-color);
}

.progress-bar {
    background: var(--theme-color);
}

input:focus,
select:focus,
textarea:focus {
    border-color: var(--theme-color);
    outline-color: var(--theme-color);
}
</style>
