<?php
session_start();

// Include the ChargePoint model
require_once('models/ChargePoint.php');

// Create view object
$view = new stdClass();
$view->pageTitle = 'Tesla Chargers';

// Create a ChargePoint model instance
$chargePointModel = new ChargePoint();

// Get Tesla Chargers
$view->chargePoints = $chargePointModel->getChargePointsByType('Tesla Charger');

// Include the view
require_once(__DIR__ . '/views/tesla-charger.phtml');