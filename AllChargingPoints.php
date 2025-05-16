<?php
session_start();

// Include the ChargePoint model
require_once('models/ChargePoint.php');

// Create view object
$view = new stdClass();
$view->pageTitle = 'AllChargingPoints';

// Create a ChargePoint model instance
$chargePointModel = new ChargePoint();

// Get all charge points from database
$view->chargePoints = $chargePointModel->getAllChargePoints();

// Get filter types - Extracting unique connector types if they exist
$view->filterTypes = $chargePointModel->getAllChargerTypes();

//  PAGINATION 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 2;
$offset = ($page - 1) * $limit;

$view->chargePoints = $chargePointModel->getPaginatedChargePoints($limit, $offset);
$view->totalChargePoints = $chargePointModel->getTotalChargePointsCount();

$view->currentPage = $page;
$view->limit = $limit;


// Include the view
require_once(__DIR__ . '/views/AllChargingPoints.phtml');