<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

require_once('models/ChargePoint.php');
require_once('models/AllBookingRequestsModel.php');
require_once('models/Database.php');

$view = new stdClass();
$view->pageTitle = 'Owner Profile';
$view->message = '';
$view->error = '';
$view->userRole = $_SESSION['role'] ?? '';

try {
    $chargePointModel = new ChargePoint();

    $chargePoint = $chargePointModel->getUserChargePoint($_SESSION['user_id']);
    $view->chargePoint = $chargePoint;

    $bookingRequestModel = new AllBookingRequestsModel();
    $view->requests = $bookingRequestModel->getBookingRequestsForUser($_SESSION['user_id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'update')) {
            $chargePointData = [
                'name' => $_POST['name'] ?? '',
                'address' => $_POST['address'] ?? '',
                'postcode' => $_POST['postcode'] ?? '',
                'latitude' => $_POST['latitude'] ?? 0,
                'longitude' => $_POST['longitude'] ?? 0,
                'price_per_kwh' => $_POST['price_per_kwh'] ?? 0,
                'available_from' => $_POST['available_from'] ?? null,
                'available_to' => $_POST['available_to'] ?? null,
                'isAvailable' => $_POST['isAvailable'] ?? 'Not Available',
                'image_path' => '',
                'charger_type' => $_POST['charger_type'] ?? null // Added this line
            ];

            if (isset($_FILES['charger_image']) && $_FILES['charger_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $chargePointModel->uploadImage($_FILES['charger_image']);
                if (strpos($uploadResult, 'uploads/') === 0) {
                    $chargePointData['image_path'] = $uploadResult;
                } else {
                    $view->error = $uploadResult;
                }
            }

            if (empty($view->error)) {
                if ($_POST['action'] === 'add') {
                    $result = $chargePointModel->addChargePoint($chargePointData, $_SESSION['user_id']);
                    if ($result === true) {
                        $view->message = "Charging point added successfully!";
                        $chargePoint = $chargePointModel->getUserChargePoint($_SESSION['user_id']);
                        $view->chargePoint = $chargePoint;
                    } else {
                        $view->error = $result;
                    }
                } else if ($_POST['action'] === 'update' && isset($_POST['charge_point_id'])) {
                    $result = $chargePointModel->updateChargePoint(
                        $chargePointData, 
                        $_POST['charge_point_id'], 
                        $_SESSION['user_id']
                    );
                    if ($result === true) {
                        $view->message = "Charging point updated successfully!";
                        $chargePoint = $chargePointModel->getUserChargePoint($_SESSION['user_id']);
                        $view->chargePoint = $chargePoint;
                    } else {
                        $view->error = $result;
                    }
                }
            }
        }
        
        else if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['charge_point_id'])) {
            $result = $chargePointModel->deleteChargePoint($_POST['charge_point_id'], $_SESSION['user_id']);
            if ($result === true) {
                $view->message = "Charging point deleted successfully!";
                $view->chargePoint = null;
            } else {
                $view->error = $result;
            }
        }
        
        else if (isset($_POST['action']) && $_POST['action'] === 'update_booking_status' 
                && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
            require_once('models/BookingDetailsModel.php');  
            $bookingModel = new BookingDetailsModel();
            $result = $bookingModel->updateBookingStatus($_POST['booking_id'], $_POST['new_status']);
            
            if ($result) {
                $view->message = "Booking status updated successfully!";
                $view->requests = $bookingRequestModel->getBookingRequestsForUser($_SESSION['user_id']);
            } else {
                $view->error = "Failed to update booking status.";
            }
        }
    }

    require_once(__DIR__ . '/views/ownerProfile.phtml');
    
} catch (Exception $e) {
    error_log("Owner Profile Page Error: " . $e->getMessage());
    $view->error = "Error loading profile: " . $e->getMessage();
    require_once(__DIR__ . '/views/ownerProfile.phtml');
}
?>