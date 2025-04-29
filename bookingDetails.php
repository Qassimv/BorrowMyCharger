<?php
require_once(__DIR__ . '/models/BookingDetailsModel.php');

$view = new stdClass();
$view->pageTitle = 'Booking Details';

$bookingModel = new BookingDetailsModel();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $view->errorMessage = 'No valid booking ID specified.';
    require_once(__DIR__ . '/views/bookingDetails.phtml');
    exit;
}

$bookingId = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['accept'])) {
            if ($bookingModel->updateBookingStatus($bookingId, 'Approved')) {
                $view->successMessage = 'Booking request has been accepted.';
            } else {
                $view->errorMessage = 'Failed to accept the booking.';
            }
        } elseif (isset($_POST['decline'])) {
            if ($bookingModel->updateBookingStatus($bookingId, 'Declined')) {
                $view->successMessage = 'Booking request has been declined.';
            } else {
                $view->errorMessage = 'Failed to decline the booking.';
            }
        }
    } catch (Exception $e) {
        $view->errorMessage = 'Error processing your request: ' . htmlspecialchars($e->getMessage());
    }
}

try {
    $view->bookingDetails = $bookingModel->getBookingDetailsById($bookingId);
    
    if (!$view->bookingDetails) {
        $view->errorMessage = 'Booking not found.';
    }
} catch (Exception $e) {
    $view->errorMessage = 'Error retrieving booking details: ' . htmlspecialchars($e->getMessage());
}

require_once(__DIR__ . '/views/bookingDetails.phtml');
