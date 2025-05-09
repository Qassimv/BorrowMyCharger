<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once('models/Database.php');
require_once('models/ChargePoint.php');

// Initialize view object
$view = new stdClass();

// Get all charge points
$chargePoint = new ChargePoint();
$view->chargePoints = $chargePoint->getAllChargePoints();

// Get all users
$db = Database::getInstance()->getdbConnection();
$stmt = $db->prepare("SELECT * FROM users_pr ORDER BY created_at DESC");
$stmt->execute();
$view->users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for query results in session
if (isset($_SESSION['queryResults'])) {
    $view->queryResults = $_SESSION['queryResults'];
    unset($_SESSION['queryResults']);
}

// Check for errors in session
if (isset($_SESSION['error'])) {
    $view->error = $_SESSION['error'];
    unset($_SESSION['error']);
}

require_once('views/adminprofile.phtml'); 