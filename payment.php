<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$view = new stdClass();
$view->pageTitle = 'payment';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('models/PaymentModel.php');

    $errors = [];

    $user_id = 1;
    $booking_id = 1;
    $amount = isset($_POST['amount']) ? str_replace('$', '', $_POST['amount']) : 0.00;
    $cardNumber = isset($_POST['CardNumber']) ? trim($_POST['CardNumber']) : '';
    $expiryDate = isset($_POST['ExpiryDate']) ? trim($_POST['ExpiryDate']) : '';
    $cvv = isset($_POST['CVV']) ? trim($_POST['CVV']) : '';

    if (!preg_match('/^\d{16}$/', str_replace(' ', '', $cardNumber))) {
        $errors[] = "Invalid card number.";
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) {
        $errors[] = "Invalid expiry date format.";
    } else {
        list($month, $year) = explode('/', $expiryDate);
        $currentYear = (int) date('y');
        $currentMonth = (int) date('m');
        if ((int)$year < $currentYear || ((int)$year == $currentYear && (int)$month < $currentMonth)) {
            $errors[] = "Card has expired.";
        }
    }

    if (!preg_match('/^\d{3}$/', $cvv)) {
        $errors[] = "Invalid CVV.";
    }
    if (!is_numeric($amount)) {
        $errors[] = "Amount must be a number.";
    } elseif ((float)$amount <= 0) {
        $errors[] = "Payment amount must be greater than zero.";
    }

    if (!empty($errors)) {
        header('Location: payment.php?status=failed&error=' . urlencode(implode(', ', $errors)));
        exit;
    }

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
