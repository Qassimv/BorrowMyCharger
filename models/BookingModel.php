<?php
require_once('Database.php');

class BookingModel
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance();
            if (!$this->db) {
                throw new Exception("Failed to initialize Database instance.");
            }
        } catch (Exception $e) {
            error_log("Constructor Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new booking in the database
     * 
     * @param int $chargePointId Charge point ID
     * @param string $startDatetime Start datetime
     * @param string $endDatetime End datetime
     * @param float $cost Estimated cost
     * @return bool Success or failure
     */
    public function createBooking($chargePointId, $startDatetime, $endDatetime, $cost)
    {
        try {
            if (!is_int($chargePointId)) {
                throw new InvalidArgumentException("Invalid chargePointId. Expected integer, got: " . gettype($chargePointId));
            }

            if (!is_float($cost)) {
                throw new InvalidArgumentException("Invalid cost. Expected float, got: " . gettype($cost));
            }

            if (!$this->db) {
                throw new Exception("Database instance is null.");
            }

            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }

            if (!$this->isValidDate($startDatetime)) {
                throw new InvalidArgumentException("Invalid startDatetime format: " . $startDatetime);
            }
            if (!$this->isValidDate($endDatetime)) {
                throw new InvalidArgumentException("Invalid endDatetime format: " . $endDatetime);
            }

            $startDatetimeFormatted = date('Y-m-d H:i:s', strtotime($startDatetime));
            $endDatetimeFormatted = date('Y-m-d H:i:s', strtotime($endDatetime));

            //hard coded 
            $userId = 1;

            $stmt = $conn->prepare("
                INSERT INTO bookings_pr (charge_point_id, user_id, start_datetime, end_datetime, cost, status)
                VALUES (:charge_point_id, :user_id, :start_datetime, :end_datetime, :cost, 'Pending')
            ");

            if (!$stmt) {
                $error = $conn->errorInfo();
                throw new Exception("Prepare statement failed: " . implode(", ", $error));
            }

            $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':start_datetime', $startDatetimeFormatted);
            $stmt->bindParam(':end_datetime', $endDatetimeFormatted);
            $stmt->bindParam(':cost', $cost);

            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                throw new Exception("SQL execution failed: " . implode(", ", $error));
            }

            return true;

        } catch (Exception $e) {
            error_log("BookingModel::createBooking Error: " . $e->getMessage());
            return false;
        }
    }

    private function isValidDate($date)
    {
        return (bool)strtotime($date);
    }
}
?>
