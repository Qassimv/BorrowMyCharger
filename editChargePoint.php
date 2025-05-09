<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once('models/Database.php');
require_once('models/ChargePoint.php');

$chargePoint = new ChargePoint();
$error = '';
$success = '';

// Get charge point data if ID is provided
if (isset($_GET['id'])) {
    $point = $chargePoint->getChargePointById($_GET['id']);
    if (!$point) {
        header('Location: adminProfile.php');
        exit;
    }
} else {
    header('Location: adminProfile.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'],
        'address' => $_POST['address'],
        'postcode' => $_POST['postcode'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude'],
        'price_per_kwh' => $_POST['price_per_kwh'],
        'available_from' => $_POST['available_from'],
        'available_to' => $_POST['available_to'],
        'isAvailable' => $_POST['isAvailable'],
        'charger_type' => $_POST['charger_type'],
        'status' => $_POST['status']
    ];

    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $chargePoint->uploadImage($_FILES['image']);
        if (is_string($uploadResult)) {
            $data['image_path'] = $uploadResult;
        } else {
            $error = $uploadResult;
        }
    }

    if (empty($error)) {
        $result = $chargePoint->updateChargePointAdmin($data, $_GET['id']);
        if ($result === true) {
            $success = 'Charge point updated successfully';
            $point = $chargePoint->getChargePointById($_GET['id']); // Refresh data
        } else {
            $error = $result;
        }
    }
}
?>

<?php require_once('views/template/header.phtml'); ?>

<div class="container-fluid py-5 custom-bg">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card bg-dark text-white shadow">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4">Edit Charging Point</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <!-- Form content remains the same -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('views/template/footer.phtml'); ?>