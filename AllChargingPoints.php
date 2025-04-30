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

// Include the view
require_once(__DIR__ . '/views/AllChargingPoints.phtml');