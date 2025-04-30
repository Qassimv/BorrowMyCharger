<?php
session_start();

// Check if user is logged in and is a homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: Login.php');
    exit();
}

require_once('models/ChargePoint.php');

// Create view object (keeping the original structure)
$view = new stdClass();
$view->pageTitle = 'ownerprofile'; // Changed from 'adminprofile' to 'ownerprofile'
$view->message = '';
$view->error = '';

// Create a new ChargePoint instance
$chargePointModel = new ChargePoint();

// Get user's charge point if exists
$chargePoint = $chargePointModel->getUserChargePoint($_SESSION['user_id']);
$view->chargePoint = $chargePoint;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add or update charge point
    if (isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'update')) {
        // Prepare data
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
            'image_path' => '' // Default empty
        ];

        // Handle image upload if a file was provided
        if (isset($_FILES['charger_image']) && $_FILES['charger_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $chargePointModel->uploadImage($_FILES['charger_image']);
            if (strpos($uploadResult, 'uploads/') === 0) {
                // Successful upload - path returned
                $chargePointData['image_path'] = $uploadResult;
            } else {
                // Error message returned
                $view->error = $uploadResult;
            }
        }

        // If we have an error from image upload, don't proceed with saving
        if (empty($view->error)) {
            if ($_POST['action'] === 'add') {
                $result = $chargePointModel->addChargePoint($chargePointData, $_SESSION['user_id']);
                if ($result === true) {
                    $view->message = "Charging point added successfully!";
                    // Refresh the charge point data
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
                    // Refresh the charge point data
                    $chargePoint = $chargePointModel->getUserChargePoint($_SESSION['user_id']);
                    $view->chargePoint = $chargePoint;
                } else {
                    $view->error = $result;
                }
            }
        }
    }
    
    // Delete charge point
    else if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['charge_point_id'])) {
        $result = $chargePointModel->deleteChargePoint($_POST['charge_point_id'], $_SESSION['user_id']);
        if ($result === true) {
            $view->message = "Charging point deleted successfully!";
            $view->chargePoint = null; // Clear the charge point data
        } else {
            $view->error = $result;
        }
    }
}

// Include the view template (keeping the original structure)
require_once(__DIR__ . '/views/ownerProfile.phtml');