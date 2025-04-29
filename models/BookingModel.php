<?php
require_once('Database.php');
class BookingModel
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
        if (!$this->db) {
            throw new Exception("Failed to initialize Database instance.");
        }
    }
    public function createBooking($chargePointId, $startDatetime, $endDatetime, $pricePerKwh)
    {
        try {
            session_start();
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("User not logged in.");
            }
            $userId = $_SESSION['user_id'];
            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }
            
            $start = DateTime::createFromFormat('Y-m-d\TH:i', $startDatetime);
            $end = DateTime::createFromFormat('Y-m-d\TH:i', $endDatetime);
            
            if (!$start || !$end) {
                throw new Exception("Invalid date format provided.");
            }
            
            $startDatetimeFormatted = $start->format('Y-m-d H:i:s');
            $endDatetimeFormatted = $end->format('Y-m-d H:i:s');
            
            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            $AVG_KWH_PER_HOUR = 10; 
            $estimatedKwh = $hours * $AVG_KWH_PER_HOUR;
            $cost = $estimatedKwh * $pricePerKwh;
            
            $stmt = $conn->prepare("
                INSERT INTO bookings_pr (charge_point_id, user_id, start_datetime, end_datetime, cost, status)
                VALUES (:charge_point_id, :user_id, :start_datetime, :end_datetime, :cost, 'Pending')
            ");
            
            $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':start_datetime', $startDatetimeFormatted);
            $stmt->bindParam(':end_datetime', $endDatetimeFormatted);
            $stmt->bindParam(':cost', $cost);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("BookingModel::createBooking Error: " . $e->getMessage());
            return false;
        }
    }
}
?>