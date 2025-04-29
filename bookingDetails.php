<?php
require_once(__DIR__ . '/models/BookingDetailsModel.php');

$view = new stdClass();
$view->pageTitle = 'Booking Details';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $view->errorMessage = 'No booking ID specified.';
    require_once(__DIR__ . '/views/bookingDetails.phtml');
    exit;
}

$bookingId = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $bookingModel = new BookingDetailsModel();
        
        if (isset($_POST['accept'])) {
            $bookingModel->updateBookingStatus($bookingId, 'Approved');
            $view->successMessage = 'Booking request has been accepted.';
        } elseif (isset($_POST['decline'])) {
            $bookingModel->updateBookingStatus($bookingId, 'Declined');
            $view->successMessage = 'Booking request has been declined.';
        }
    } catch (Exception $e) {
        $view->errorMessage = 'Error processing your request: ' . $e->getMessage();
    }
}

try {
    $bookingModel = new BookingDetailsModel();
    $view->bookingDetails = $bookingModel->getBookingDetailsById($bookingId);
    
    if (!$view->bookingDetails) {
        $view->errorMessage = 'Booking not found.';
    }
} catch (Exception $e) {
    $view->errorMessage = 'Error retrieving booking details: ' . $e->getMessage();
}

require_once(__DIR__ . '/views/bookingDetails.phtml');