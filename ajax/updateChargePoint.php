<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

require_once('../models/Database.php');
require_once('../models/ChargePoint.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chargePoint = new ChargePoint();

    // Only check for fields that are actually in the form (no image_path)
    $required = ['charge_point_id', 'name', 'address', 'postcode', 'latitude', 'longitude', 'price_per_kwh', 'available_from', 'available_to', 'isAvailable', 'charger_type'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }

    try {
        // Call the updateChargePointAdmin function (which does NOT update image_path)
        $result = $chargePoint->updateChargePointAdmin($_POST, $_POST['charge_point_id']);
        if ($result === true) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => $result]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request.']);
}