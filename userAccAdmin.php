<?php
session_start();

$view = new stdClass();
$view->pageTitle = 'userAccAdmin';
require_once(__DIR__ . '/views/userAccAdmin.phtml');
?>
