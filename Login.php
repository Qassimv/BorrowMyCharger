<?php
session_start();

$view = new stdClass();
$view->pageTitle = 'Login';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['Username']);
    $password = trim($_POST['Password']);

    $userModel = new User();

    // Check if the username is an email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        // If it's an email, fetch user by email
        $user = $userModel->getUserByEmail($username);
    } else {
        // Otherwise, fetch by username
        $user = $userModel->getUserByUsername($username);
    }

    if ($user) {
        // First check if password is hashed, and then compare accordingly
        if (password_get_info($user['passHash'])['algoName'] !== 'unknown') {
            // If it's hashed, verify it
            if (password_verify($password, $user['passHash'])) {
                if ($user['is_approved']) {
                    // Regenerate session to prevent session fixation attacks
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Your account is not approved yet.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            // If the password is not hashed, compare plain text
            if ($password === $user['passHash']) {
                if ($user['is_approved']) {
                    // Regenerate session to prevent session fixation attacks
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Your account is not approved yet.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        }
    } else {
        $error = "Invalid username or password.";
    }
}

require_once 'views/Login.phtml';
