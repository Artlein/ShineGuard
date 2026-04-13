<?php
/**
 * SHINEGUARD API GATEWAY v1
 * Responsibility: Routing incoming API requests to the appropriate service.
 */

require_once __DIR__ . '/../../dbconnect.php';
require_once __DIR__ . '/../../src/Services/IdentityService.php';
require_once __DIR__ . '/../../src/Services/IOTService.php';
require_once __DIR__ . '/../../src/Services/AuditService.php';
require_once __DIR__ . '/../../src/Services/SecurityService.php';

use ShineGuard\Services\IdentityService;
use ShineGuard\Services\IOTService;
use ShineGuard\Services\AuditService;

header('Content-Type: application/json');

// 1. GLOBAL INFRASTRUCTURE FIREWALL (Rate Limiting)
if (!AuditService::checkRateLimit($conn, 'api_gateway', 60, 1)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too Many Requests', 'message' => 'Infrastructure rate limit reached. Access throttled.']);
    exit();
}

// 2. IDENTITY CHECK
if (!IdentityService::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit();
}

$service = $_GET['service'] ?? '';
$action  = $_GET['action']  ?? '';

try {
    switch ($service) {
        case 'Identity':
            handleIdentity($action);
            break;
        case 'IOT':
            handleIOT($action);
            break;
        case 'Audit':
            handleAudit($action);
            break;
        case 'Health':
            handleHealth();
            break;
        default:
            throw new Exception("Service '$service' not found.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * IDENTITY SERVICE ROUTER
 */
function handleIdentity($action) {
    switch ($action) {
        case 'getProfile':
            echo json_encode([
                'success' => true, 
                'data' => [
                    'username' => $_SESSION['username'],
                    'role' => IdentityService::getUserRole(),
                    'user_id' => IdentityService::getUserId()
                ]
            ]);
            break;
        case 'canDo':
            $permission = $_GET['perm'] ?? '';
            echo json_encode(['success' => true, 'authorized' => IdentityService::canDo($permission)]);
            break;
        default:
            throw new Exception("Action '$action' not defined for Identity service.");
    }
}

/**
 * IOT SERVICE ROUTER
 */
function handleIOT($action) {
    global $conn;
    switch ($action) {
        case 'getSummary':
            echo json_encode(['success' => true, 'data' => IOTService::getStreetlightSummary($conn)]);
            break;
        case 'getLatestTelemetry':
            $light_id = (int)($_GET['id'] ?? 1);
            echo json_encode(['success' => true, 'data' => IOTService::getLatestTelemetry($conn, $light_id)]);
            break;
        default:
            throw new Exception("Action '$action' not defined for IOT service.");
    }
}

/**
 * AUDIT SERVICE ROUTER
 */
function handleAudit($action) {
    global $conn;
    switch ($action) {
        case 'log':
            $act = $_POST['action_name'] ?? 'Generic Activity';
            $det = $_POST['details'] ?? '';
            AuditService::logActivity($conn, IdentityService::getUserId(), $act, $det);
            echo json_encode(['success' => true]);
            break;
        default:
            throw new Exception("Action '$action' not defined for Audit service.");
    }
}

/**
 * HEALTH CHECK (Observability)
 */
function handleHealth() {
    global $conn;
    $db_status = $conn->ping() ? 'Online' : 'Offline';
    
    echo json_encode([
        'success' => true,
        'services' => [
            'Identity' => 'Healthy',
            'IOT'      => 'Healthy',
            'Audit'    => 'Healthy',
            'Database' => $db_status
        ],
        'version' => '1.0.0-micro',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
