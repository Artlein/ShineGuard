<?php
// EMERGENCY CREDENTIAL PATCHER
// Run this once to fix the .env file on AWS server

$envContent = <<<EOD
# ShineGuard Environment Configuration
# DO NOT COMMIT THIS FILE TO VERSION CONTROL

DB_HOST=localhost
DB_NAME=Hulo
DB_USER=root
DB_PASS=
DB_USER_AWS=shineguard
DB_PASS_AWS=ShineGuard2026

MAILTRAP_TOKEN=1455d7c786b90dcc3450dfd347ca82ba
MAILTRAP_INBOX=4546141
SYSTEM_EMAIL=noreply@hulo.barangay.ph
SYSTEM_NAME="ShineGuard Security"

# Firebase Cloud Credentials
FIREBASE_API_KEY=AIzaSyAiTzPjpVKq63mG99RCq1uE_nVpNqoydmo
FIREBASE_AUTH_DOMAIN=shineguardhulo-1h.firebaseapp.com
FIREBASE_DATABASE_URL=https://shineguardhulo-1h-default-rtdb.asia-southeast1.firebasedatabase.app
FIREBASE_PROJECT_ID=shineguardhulo-1h
FIREBASE_STORAGE_BUCKET=shineguardhulo-1h.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=909607860439
FIREBASE_APP_ID=1:909607860439:web:9f0db5990c80f182465892
FIREBASE_MEASUREMENT_ID=G-HDG8GWXB06

# MQTT Broker Credentials
MQTT_SERVER=broker.emqx.io
MQTT_PORT=1883
MQTT_USER=
MQTT_PASS=
EOD;

$file = __DIR__ . '/.env';
if (file_put_contents($file, $envContent)) {
    echo "<h1>✅ SUCCESS</h1>";
    echo "<p>.env file has been reconstructed with new credentials.</p>";
    echo "<p>Path: <b>$file</b></p>";
} else {
    echo "<h1>❌ FAILED</h1>";
    echo "<p>Permissions issue? Could not write to .env</p>";
}
?>
