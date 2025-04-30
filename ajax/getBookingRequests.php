<?php
session_start();
header('Content-Type: application/json');

require_once('../models/AllBookingRequestsModel.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $model = new AllBookingRequestsModel();
    $requests = $model->getBookingRequestsForUser($userId);
    echo json_encode($requests);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
