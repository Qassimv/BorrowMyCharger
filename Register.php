<?php
$view = new stdClass();
$view->pageTitle = 'Register';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $full_name = trim($_POST['FullName']);
    $username = trim($_POST['Username']);
    $password = trim($_POST['Password']);
    
    // Create an instance of the User model
    $user = new User();
    
    // Call the register method
    $registration_result = $user->register($username, $password, $full_name);
    
    // Check the result and handle accordingly
    if ($registration_result === true) {
        // Redirect to login page after successful registration
        header("Location: Login.php");
        exit;
    } else {
        // Store the error message in a variable instead of echoing it
        $registration_error = $registration_result;
    }
}

require_once(__DIR__ . '/views/Register.phtml');
?>