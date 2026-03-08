<?php
/**
 * firebase_config.example.php
 * 
 * Copy this file to firebase_config.php and fill in your own credentials.
 * NEVER commit firebase_config.php to version control.
 */

class FirebaseConfig {
    
    const API_KEY = 'YOUR_FIREBASE_API_KEY_HERE';
    const AUTH_DOMAIN = 'YOUR_PROJECT_ID.firebaseapp.com';
    const DATABASE_URL = 'https://YOUR_PROJECT_ID-default-rtdb.asia-southeast1.firebasedatabase.app';
    const PROJECT_ID = 'YOUR_PROJECT_ID';
    const STORAGE_BUCKET = 'YOUR_PROJECT_ID.firebasestorage.app';
    const MESSAGING_SENDER_ID = 'YOUR_MESSAGING_SENDER_ID';
    const APP_ID = 'YOUR_APP_ID';

    const IOT_DEVICES = [
        'SG-NODE2' => [
            'name' => 'Shine Guard Node 1',
            'type' => 'ESP32',
            'mysql_id' => 'SL-001',
            'status' => 'active'
        ]
    ];

    const NODE_MAPPING = [
        'SG-NODE2' => 'SL-001'
    ];

    const ENDPOINTS = [
        'sensor'   => '/SG-NODE2/Sensor.json',
        'actuator' => '/SG-NODE2/Actuator.json',
        'health'   => '/SG-NODE2/Health.json',
        'control'  => '/SG-NODE2/Control.json'
    ];

    // ... rest of the class methods remain the same
}
?>
