
<?php

/*$view = new stdClass();
$view->pageTitle = 'AllChargingPoints';
require_once(__DIR__ . '/views/AllChargingPoints.phtml');*/

require_once(__DIR__ . '/models/Database.php');
require_once(__DIR__ . '/models/ChargePointData.php');

$view = new stdClass();
$view->pageTitle = 'AllChargingPoints';


$db = new Database();
$pdo = $db->getConnection();


$model = new ChargePointModel($pdo);
$view->chargingPoints = $model->searchChargingPoints('', 'all');
$view->chargerTypes = $model->getAllChargerTypes(); 

require_once(__DIR__ . '/views/AllChargingPoints.phtml');




