<?php
session_start();

$view = new stdClass();
$view->pageTitle = 'User Account Admin';

// Include the User model
require_once 'models/User.php';

// Create an instance of the User model
$userModel = new User();

// Fetch the user by their ID (from the URL or session)
$userId = isset($_GET['id']) ? $_GET['id'] : null; // Get user ID from URL query parameter

if ($userId) {
    // Fetch the user details by ID
    $user = $userModel->getUserById($userId);

    // Check if user exists
    if ($user) {
        $view->user = $user; // Pass user data to the view
    } else {
        // Handle case if user does not exist
        echo "User not found.";
        exit;
    }
} else {
    // Handle case if no user ID is provided
    echo "No user specified.";
    exit;
}

// Handle actions (Approve, Suspend, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Action type (approve, suspend, delete)
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'approve':
            if ($view->user['is_approved'] == 0) { // Check if the user is not approved
                $userModel->approveUser($userId);
                header("Location: userAccAdmin.php?id=$userId"); // Redirect after action
            } else {
                // Optionally, you can show a message or redirect to a page
                echo "User is already approved.";
                exit;
            }
            break;

        case 'suspend':
            $userModel->suspendUser($userId);
            header("Location: userAccAdmin.php?id=$userId"); // Redirect after action
            break;

        case 'delete':
            $userModel->deleteUser($userId);
            header("Location: adminProfile.php"); // Redirect to list page after deletion
            break;

        default:
            echo "Invalid action.";
            exit;
    }
}

require_once(__DIR__ . '/views/userAccAdmin.phtml');
