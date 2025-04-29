<?php
session_start();
require_once('models/AllBookingRequestsModel.php');
require_once('models/Database.php');

$view = new stdClass();
$view->pageTitle = 'Owner Profile';
$view->message = '';

try {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $view->userRole = $_SESSION['role'] ?? '';
    
    $bookingRequestModel = new AllBookingRequestsModel();
    $view->requests = $bookingRequestModel->getBookingRequestsForUser($userId);
    
    require_once(__DIR__ . '/views/ownerProfile.phtml');
    
} catch (Exception $e) {
    error_log("Owner Profile Page Error: " . $e->getMessage());
    echo "Error loading owner profile page: " . $e->getMessage();
}
?>