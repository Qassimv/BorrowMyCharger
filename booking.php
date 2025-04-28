<?php
require_once('models/BookingModel.php');

$view = new stdClass();
$view->pageTitle = 'booking';
$view->message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $chargePointId = filter_input(INPUT_POST, 'charge_point_id', FILTER_VALIDATE_INT);
    $startDatetime = filter_input(INPUT_POST, 'start_datetime');
    $endDatetime = filter_input(INPUT_POST, 'end_datetime');
    // $cost = filter_input(INPUT_POST, 'cost');
    
    // $cost = floatval(str_replace('$', '', $cost));
    
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
                $chargePointId,
                $startDatetime,
                $endDatetime,
                // $cost
            );
        
            if ($result) {
                $view->message = 'Booking successful! Redirecting to payment...';
                header('refresh:2;url=payment.php');
                exit;
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
    const powerOutput = parseFloat(document.getElementById("powerOutput").value);

    function calculateCost() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (startDate && endDate && endDate > startDate) {
            const timeDiff = (endDate - startDate) / (1000 * 60 * 60); // hours
            const estimatedKwh = timeDiff * powerOutput * 0.8; // 80% efficiency
            const cost = estimatedKwh * pricePerKwh;

            totalCostInput.value = "$" + cost.toFixed(2);
        } else {
            totalCostInput.value = "$0.00";
        }
    }

    // 🔥 Add these event listeners:
    startDateInput.addEventListener("change", calculateCost);
    endDateInput.addEventListener("change", calculateCost);
});
</script>

</script>
';

require_once(__DIR__ . '/views/booking.phtml');
?>