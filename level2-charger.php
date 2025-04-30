<?php
session_start();

// Include the ChargePoint model
require_once('models/ChargePoint.php');

// Create view object
$view = new stdClass();
$view->pageTitle = 'Level 2 Chargers';

// Create a ChargePoint model instance
$chargePointModel = new ChargePoint();

// Get Level 2 Chargers
$view->chargePoints = $chargePointModel->getChargePointsByType('Level 2 Charger');

// Include the view
require_once(__DIR__ . '/views/level2-charger.phtml');