<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once('../models/Database.php');
require_once('../models/ChargePoint.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['charge_point_id'])) {
    $chargePoint = new ChargePoint();
    $result = $chargePoint->deleteChargePoint($_POST['charge_point_id'], null, true); // true indicates admin deletion
    
    if ($result === true) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $result]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 