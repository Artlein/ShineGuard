<?php
/**
 * 🚀 ShineGuard AWS Environment Repair Tool
 * This script regenerates the missing .env file on the AWS server to enable Firebase sync.
 */

$envContent = <<<EOD
# ShineGuard Environment Configuration (AWS REPAIRED)
DB_HOST=localhost
DB_NAME=Hulo
DB_USER=root
DB_PASS=
DB_USER_AWS=shineguard
DB_PASS_AWS=ShineGuard2026

FIREBASE_API_KEY=AIzaSyAiTzPjpVKq63mG99RCq1uE_nVpNqoydmo
FIREBASE_AUTH_DOMAIN=shineguardhulo-1h.firebaseapp.com
FIREBASE_DATABASE_URL=https://shineguardhulo-1h-default-rtdb.asia-southeast1.firebasedatabase.app
FIREBASE_PROJECT_ID=shineguardhulo-1h
FIREBASE_STORAGE_BUCKET=shineguardhulo-1h.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=909607860439
FIREBASE_APP_ID=1:909607860439:web:9f0db5990c80f182465892
FIREBASE_MEASUREMENT_ID=G-HDG8GWXB06

SYSTEM_NAME="ShineGuard Security"
SYSTEM_EMAIL=noreply@hulo.barangay.ph
EOD;

$target = __DIR__ . '/.env';

echo "<h2>🛠️ ShineGuard Environment Repair</h2>";

if (file_put_contents($target, $envContent)) {
    echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: .env file regenerated successfully.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ ERROR: Failed to write .env file. Check folder permissions.</p>";
}

echo "<hr>";
echo "<h3>🔍 Diagnostic Check</h3>";
echo "IP Address: " . $_SERVER['SERVER_ADDR'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

unlink(__FILE__); // Self-destruct for security
echo "<p><i>Security: Support script self-destructed.</i></p>";
?>
