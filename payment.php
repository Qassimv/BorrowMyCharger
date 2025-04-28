<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$view = new stdClass();
$view->pageTitle = 'payment';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('models/PaymentModel.php');

    $user_id = 1;
    $booking_id = 1;
    $amount = isset($_POST['amount']) ? str_replace('$', '', $_POST['amount']) : 0.00;

    $paymentModel = new PaymentModel();
    $success = $paymentModel->createPayment($user_id, $booking_id, $amount);

    if ($success) {
        header('Location: payment.php?status=success');
        exit;
    } else {
        header('Location: payment.php?status=failed');
        exit;
    }
} else {
    $amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : '0.00';
    require_once(__DIR__ . '/views/payment.phtml');
}
?>
