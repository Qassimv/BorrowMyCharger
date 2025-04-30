<?php
// Include the User model
require_once 'models/User.php';

// Create an instance of the User class
$userModel = new User();

// Fetch all users
$users = $userModel->getAllUsers();

// Prepare data for the view
$view = new stdClass();
$view->pageTitle = 'Admin Profile';
$view->users = $users;

// Include the view
require_once(__DIR__ . '/views/adminProfile.phtml');
?>
