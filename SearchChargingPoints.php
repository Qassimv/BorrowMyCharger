<?php
require_once(__DIR__ . '/models/Database.php');
require_once(__DIR__ . '/models/ChargePointData.php');

$db = new Database();
$pdo = $db->getConnection();

$search = $_POST['search'] ?? '';
$filter = $_POST['filter'] ?? 'all';

$model = new ChargePointModel($pdo);
$results = $model->searchChargingPoints($search, $filter);

$view = new stdClass();
$view->chargingPoints = $results;


require_once('views/ChargingPointCards.phtml');
