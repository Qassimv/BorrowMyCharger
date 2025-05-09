<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

require_once('../models/Database.php');
require_once('../models/ChargePoint.php');

if (isset($_GET['id'])) {
    $chargePointId = intval($_GET['id']);
    $chargePoint = new ChargePoint();

    try {
        $data = $chargePoint->getChargePointById($chargePointId);
        if ($data) {
            error_log("Charge point data: " . json_encode($data)); // Debugging: Log the charge point data
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            error_log("Charge point not found for ID: " . $chargePointId); // Debugging: Log the error
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Charging point not found.']);
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage()); // Debugging: Log the database error
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    error_log("Invalid request: Missing ID parameter."); // Debugging: Log the error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request.']);
}