<?php
session_start();

// Include the ChargePoint model
require_once('models/ChargePoint.php');

// Create view object
$view = new stdClass();
$view->pageTitle = 'DC Fast Chargers';

// Create a ChargePoint model instance
$chargePointModel = new ChargePoint();

// Get DC Fast Chargers
$view->chargePoints = $chargePointModel->getChargePointsByType('DC Fast Charger');

// Include the view
require_once(__DIR__ . '/views/dc-fast-charger.phtml');