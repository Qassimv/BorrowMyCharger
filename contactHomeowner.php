<?php
session_start();

require_once('models/ContactMessageModel.php');
require_once('models/Database.php');

$view = new stdClass();
$view->pageTitle = 'Contact Homeowner';
$view->message = '';
//hard code 
$recipientId = $_GET['recipient_id'] ?? 1;

if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $senderId = $_SESSION['user_id'];
    $subject = trim($_POST['subject']);
    $messageText = trim($_POST['message']);

    if (empty($subject) || empty($messageText)) {
        $view->message = "Please fill in all fields.";
    } else {
        $contactModel = new ContactMessageModel();
        $success = $contactModel->saveMessage($senderId, $recipientId, $subject, $messageText);

        if ($success) {
            $view->message = "Your message has been sent successfully!";
        } else {
            $view->message = "Failed to send your message. Please try again.";
        }
    }
}

require_once(__DIR__ . '/views/contactHomeowner.phtml');
