<?php

class FirebaseConfig {
    
    const API_KEY = 'AIzaSyBM69Xh5_d2lhiwGEi1gz9OfNHBEyEYrSQ';
    const AUTH_DOMAIN = 'sg-hulo.firebaseapp.com';
    const DATABASE_URL = 'https://sg-hulo-default-rtdb.asia-southeast1.firebasedatabase.app';
    const PROJECT_ID = 'sg-hulo';
    const STORAGE_BUCKET = 'sg-hulo.firebasestorage.app';
    const MESSAGING_SENDER_ID = '1098036753407';
    const APP_ID = '1:1098036753407:web:a0b564a0c18d11e9a52dca';

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
        'sensor' => '/SG-NODE2/Sensor.json',
        'actuator' => '/SG-NODE2/Actuator.json',
        'health' => '/SG-NODE2/Health.json',
        'control' => '/SG-NODE2/Control.json'
    ];

    public static function getConfig() {
        return [
            'apiKey' => self::API_KEY,
            'authDomain' => self::AUTH_DOMAIN,
            'databaseURL' => self::DATABASE_URL,
            'projectId' => self::PROJECT_ID,
            'storageBucket' => self::STORAGE_BUCKET,
            'messagingSenderId' => self::MESSAGING_SENDER_ID,
            'appId' => self::APP_ID
        ];
    }

    public static function getUrl($endpoint, $nodeId = 'SG-NODE2') {
        if (!isset(self::ENDPOINTS[$endpoint])) {
            throw new Exception("Invalid endpoint: $endpoint");
        }

        $path = str_replace('SG-NODE2', $nodeId, self::ENDPOINTS[$endpoint]);
        return self::DATABASE_URL . $path;
    }

    public static function getMySQLNode($firebaseNode) {
        return self::NODE_MAPPING[$firebaseNode] ?? null;
    }

    public static function getFirebaseNode($mysqlNode) {
        $flipped = array_flip(self::NODE_MAPPING);
        return $flipped[$mysqlNode] ?? null;
    }

    public static function getIoTDevice($nodeId) {
        return self::IOT_DEVICES[$nodeId] ?? null;
    }

    public static function getAllIoTDevices() {
        return self::IOT_DEVICES;
    }

    public static function readData($endpoint, $nodeId = 'SG-NODE2') {
        $url = self::getUrl($endpoint, $nodeId);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Firebase read failed with HTTP code: $httpCode");
        }
        
        return json_decode($response, true);
    }

    public static function writeData($endpoint, $data, $nodeId = 'SG-NODE2') {
        $url = self::getUrl($endpoint, $nodeId);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Firebase write failed with HTTP code: $httpCode");
        }
        
        return json_decode($response, true);
    }

    public static function updateField($endpoint, $field, $value, $nodeId = 'SG-NODE2') {
        $url = self::DATABASE_URL . '/' . $nodeId . '/' . $endpoint . '/' . $field . '.json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($value));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Firebase update failed with HTTP code: $httpCode");
        }
        
        return json_decode($response, true);
    }

    public static function sendIoTCommand($nodeId, $command, $value) {
        $allowedCommands = ['mode', 'brightnessPercent'];
        
        if (!in_array($command, $allowedCommands)) {
            throw new Exception("Invalid command: $command");
        }
        
        return self::updateField('Control', $command, $value, $nodeId);
    }

    public static function getSensorData($nodeId = 'SG-NODE2') {
        return self::readData('sensor', $nodeId);
    }

    public static function getActuatorData($nodeId = 'SG-NODE2') {
        return self::readData('actuator', $nodeId);
    }

    public static function getHealthData($nodeId = 'SG-NODE2') {
        return self::readData('health', $nodeId);
    }

    public static function isDeviceOnline($nodeId = 'SG-NODE2') {
        try {
            $data = self::getSensorData($nodeId);
            return !empty($data);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>