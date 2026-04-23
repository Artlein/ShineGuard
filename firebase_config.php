<?php

class FirebaseConfig {
    
    public static function getConstant($key) {
        $envMap = [
            'API_KEY'             => 'FIREBASE_API_KEY',
            'AUTH_DOMAIN'         => 'FIREBASE_AUTH_DOMAIN',
            'DATABASE_URL'        => 'FIREBASE_DATABASE_URL',
            'PROJECT_ID'          => 'FIREBASE_PROJECT_ID',
            'STORAGE_BUCKET'      => 'FIREBASE_STORAGE_BUCKET',
            'MESSAGING_SENDER_ID' => 'FIREBASE_MESSAGING_SENDER_ID',
            'APP_ID'              => 'FIREBASE_APP_ID',
            'MEASUREMENT_ID'      => 'FIREBASE_MEASUREMENT_ID'
        ];
        $envKey = $envMap[$key] ?? '';
        return $_ENV[$envKey] ?? $_SERVER[$envKey] ?? getenv($envKey) ?: '';
    }

    const IOT_DEVICES = [
        'SG-NODE2' => [
            'name' => 'Intelligence Node 2',
            'type' => 'ESP32 Control',
            'mysql_id' => 'SL-001',
            'status' => 'active'
        ],
        'SG-NODE3' => [
            'name' => 'Intelligence Node 3',
            'type' => 'ESP32 Control',
            'mysql_id' => 'SL-002',
            'status' => 'active'
        ]
    ];

    const NODE_MAPPING = [
        'SG-NODE2' => 'SL-001',
        'SG-NODE3' => 'SL-002'
    ];

    const ENDPOINTS = [
        'sensor' => '/Sensor.json',
        'actuator' => '/Actuator.json',
        'health' => '/Health.json',
        'control' => '/Control.json',
        'predictive' => '/Predictive.json'
    ];

    /**
     * Retrieves the specific Firebase configuration for a node.
     * Supports multiple Firebase projects/databases.
     */
    public static function getNodeConfig($nodeId = 'SG-NODE2') {
        // DEFAULT CONFIG (NODE 2)
        $config = [
            'apiKey'            => self::getConstant('API_KEY'),
            'authDomain'        => self::getConstant('AUTH_DOMAIN'),
            'databaseURL'       => self::getConstant('DATABASE_URL'),
            'projectId'         => self::getConstant('PROJECT_ID'),
            'storageBucket'     => self::getConstant('STORAGE_BUCKET'),
            'messagingSenderId' => self::getConstant('MESSAGING_SENDER_ID'),
            'appId'             => self::getConstant('APP_ID'),
            'measurementId'     => self::getConstant('MEASUREMENT_ID')
        ];

        // NODE 3 SPECIFIC (Different Firebase)
        if ($nodeId === 'SG-NODE3') {
            $config['apiKey']      = self::getConstant('NODE3_API_KEY') ?: $config['apiKey'];
            $config['databaseURL'] = self::getConstant('NODE3_DATABASE_URL') ?: $config['databaseURL'];
            $config['projectId']   = self::getConstant('NODE3_PROJECT_ID') ?: $config['projectId'];
        }

        return $config;
    }

    public static function getConfig() {
        return self::getNodeConfig();
    }

    public static function getUrl($endpoint, $nodeId = 'SG-NODE2') {
        if (!isset(self::ENDPOINTS[$endpoint])) {
            throw new Exception("Invalid endpoint: $endpoint");
        }
        $config = self::getNodeConfig($nodeId);
        $baseUrl = rtrim($config['databaseURL'], '/');
        return $baseUrl . '/' . $nodeId . self::ENDPOINTS[$endpoint];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local/staging flexibility
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null; // Graceful fail for multi-node
        }
        
        return json_decode($response, true);
    }

    public static function writeData($endpoint, $data, $nodeId = 'SG-NODE2') {
        $url = self::getUrl($endpoint, $nodeId);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200);
    }

    public static function updateField($endpoint, $field, $value, $nodeId = 'SG-NODE2') {
        $config = self::getNodeConfig($nodeId);
        $baseUrl = rtrim($config['databaseURL'], '/');
        $url = $baseUrl . '/' . $nodeId . '/' . $endpoint . '/' . $field . '.json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($value));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200);
    }

    public static function sendIoTCommand($nodeId, $command, $value) {
        $allowedCommands = ['mode', 'brightnessPercent'];
        if (!in_array($command, $allowedCommands)) return false;
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
            $data = self::readData('sensor', $nodeId);
            return ($data !== null && !empty($data));
        } catch (Exception $e) {
            return false;
        }
    }
}
?>