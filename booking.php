<?php
session_start();
require_once('models/BookingModel.php');
require_once('models/ChargePoint.php');
require_once('models/Database.php');

$view = new stdClass();
$view->pageTitle = 'Booking';
$view->message = '';

try {
    $chargePointId = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : null;
    
    if (!$chargePointId) {
        header('Location: index.php');
        exit;
        die('NO ID: ' . var_export($_GET, true));
    }
    
    $chargePointModel = new ChargePoint();
    $chargePoint = $chargePointModel->getChargePointById($chargePointId);
    
    if (!$chargePoint) {
        $view->message = 'Charging point not found.';
    } else {
        $view->chargePoint = $chargePoint;
        $view->pricePerKwh = (float)$chargePoint['price_per_kwh'];
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
        $submittedChargePointId = filter_input(INPUT_POST, 'charge_point_id', FILTER_VALIDATE_INT);
        $startDatetime = filter_input(INPUT_POST, 'start_datetime');
        $endDatetime = filter_input(INPUT_POST, 'end_datetime');
        
        $startDateTime = new DateTime($startDatetime);
        $endDateTime = new DateTime($endDatetime);
        $now = new DateTime();
        
        if ($startDateTime < $now) {
            $view->message = 'Start time cannot be in the past.';
        } elseif ($endDateTime <= $startDateTime) {
            $view->message = 'End time must be after start time.';
        } else {
            try {
                $bookingModel = new BookingModel();
                $result = $bookingModel->createBooking(
                    $submittedChargePointId,
                    $startDatetime,
                    $endDatetime,
                    $view->pricePerKwh
                );
                
                if ($result) {
                    $view->message = 'Request sent, waiting for approval.';
                } else {
                    $view->message = 'Booking failed. Please check server logs.';
                }
            } catch (Exception $e) {
                $view->message = 'Error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
    
    $view->script = '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const startDateInput = document.getElementById("startDate");
            const endDateInput = document.getElementById("endDate");
            const totalCostInput = document.getElementById("totalCost");
            const pricePerKwh = parseFloat(document.getElementById("pricePerKwh").value);
            const AVG_KWH_PER_HOUR = 10; // Fixed average usage
            
            function calculateCost() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (startDate && endDate && endDate >= startDate) {
                    const hours = (endDate - startDate) / (1000 * 60 * 60);
                    const estimatedKwh = hours * AVG_KWH_PER_HOUR;
                    const cost = estimatedKwh * pricePerKwh;
                    totalCostInput.value = "$" + cost.toFixed(2);
                } else {
                    totalCostInput.value = "$0.00";
                }
            }
            
            startDateInput.addEventListener("change", calculateCost);
            endDateInput.addEventListener("change", calculateCost);
        });
    </script>
    ';
    
    require_once(__DIR__ . '/views/booking.phtml');
    
} catch (Exception $e) {
    error_log("Booking Page Error: " . $e->getMessage());
    echo "Error loading booking page: " . $e->getMessage();
}
?>