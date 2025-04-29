<?php

require_once 'Database.php'; 

class AllBookingRequestsModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        if (!$this->db) {
            throw new Exception("Failed to initialize Database instance.");
        }
    }

    public function getBookingRequestsForUser($userId)
    {
        if (!$userId) {
            throw new Exception("Invalid user ID provided.");
        }

        try {
            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }

            $sql = "
                SELECT 
                    b.booking_id AS request_id, 
                    b.start_datetime AS start_date, 
                    b.end_datetime AS end_date, 
                    cp.address AS location
                FROM 
                    bookings_pr b
                INNER JOIN 
                    charge_points_pr cp ON b.charge_point_id = cp.charge_point_id
                WHERE 
                    cp.user_id = :user_id
                ORDER BY 
                    b.start_datetime DESC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("AllBookingRequestsModel::getBookingRequestsForUser Error: " . $e->getMessage());
            throw $e;
        }
    }
}