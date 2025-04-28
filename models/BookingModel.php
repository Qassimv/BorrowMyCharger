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
    public function createBooking($chargePointId, $startDatetime, $endDatetime)
    {
        try {
            if (!is_int($chargePointId)) {
                throw new InvalidArgumentException("Invalid chargePointId. Expected integer, got: " . gettype($chargePointId));
            }
    
            if (!$this->db) {
                throw new Exception("Database instance is null.");
            }
    
            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }
    
            if (!$this->isValidDate($startDatetime) || !$this->isValidDate($endDatetime)) {
                throw new InvalidArgumentException("Invalid datetime format.");
            }
    
            $startDatetimeFormatted = date('Y-m-d H:i:s', strtotime($startDatetime));
            $endDatetimeFormatted = date('Y-m-d H:i:s', strtotime($endDatetime));
    
            // Fetch pricePerKwh from charge_points table
            $stmt = $conn->prepare("SELECT price_per_kwh, power_output FROM charge_points WHERE id = :id");
            $stmt->bindParam(':id', $chargePointId, PDO::PARAM_INT);
            $stmt->execute();
            $chargePoint = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$chargePoint) {
                throw new Exception("Charge point not found.");
            }
    
            $pricePerKwh = (float)$chargePoint['price_per_kwh'];
            $powerOutput = (float)$chargePoint['power_output']; // assuming you store this too (kW)
    
            if ($pricePerKwh <= 0 || $powerOutput <= 0) {
                throw new Exception("Invalid charger settings in database.");
            }
    
            $start = new DateTime($startDatetimeFormatted);
            $end = new DateTime($endDatetimeFormatted);
            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            $estimatedKwh = $hours * $powerOutput * 0.8; 
            $cost = $estimatedKwh * $pricePerKwh;
    
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
