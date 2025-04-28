<?php
require_once('models/BookingModel.php');
require_once('models/Database.php');

$view = new stdClass();
$view->pageTitle = 'booking';
$view->message = '';

try {
    $db = Database::getInstance();
    $dbConnection = $db->getdbConnection();

    $chargePointId = 1; // hardcoded for now
    $stmt = $dbConnection->prepare("SELECT price_per_kwh FROM charge_points_pr WHERE charge_point_id = :id");
    $stmt->bindParam(':id', $chargePointId, PDO::PARAM_INT);
    $stmt->execute();
    $chargePoint = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chargePoint) {
        $view->pricePerKwh = (float)$chargePoint['price_per_kwh'];
    } else {
        $view->pricePerKwh = 0.30; // Default if not set in db
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
        $chargePointId = filter_input(INPUT_POST, 'charge_point_id', FILTER_VALIDATE_INT);
        $startDatetime = filter_input(INPUT_POST, 'start_datetime');
        $endDatetime = filter_input(INPUT_POST, 'end_datetime');

        $startDatetime = $startDatetime . ' 00:00:00';
        $endDatetime = $endDatetime . ' 23:59:59';

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
            const endDateCopy = new Date(endDate);
            endDateCopy.setDate(endDateCopy.getDate() + 1); // include full end day
            const hours = (endDateCopy - startDate) / (1000 * 60 * 60);

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
