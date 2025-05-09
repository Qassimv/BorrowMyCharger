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

    // Preserve the existing image_path if not provided
    if (!isset($_POST['image_path'])) {
        $db = Database::getInstance()->getdbConnection();
        $stmt = $db->prepare('SELECT image_path FROM charge_points_pr WHERE charge_point_id = :id');
        $stmt->bindParam(':id', $_POST['charge_point_id'], PDO::PARAM_INT);
        $stmt->execute();
        $existingChargePoint = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingChargePoint) {
            $_POST['image_path'] = $existingChargePoint['image_path'];
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Charge point not found.']);
            exit;
        }
    }

    try {
        // Call the existing updateChargePointAdmin function
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