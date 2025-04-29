<?php
require_once 'Database.php';

class BookingDetailsModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        if (!$this->db) {
            throw new Exception("Failed to initialize Database instance.");
        }
    }

    public function getBookingDetailsById($bookingId)
    {
        if (!$bookingId) {
            throw new Exception("Invalid booking ID provided.");
        }

        try {
            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }

            $sql = "
            SELECT
                b.booking_id,
                b.start_datetime,
                b.end_datetime,
                b.status,
                b.cost,
                cp.name,
                cp.address AS location,
                cp.price_per_kwh,
                u.username AS user_username 
            FROM
                bookings_pr b
            INNER JOIN
                charge_points_pr cp ON b.charge_point_id = cp.charge_point_id
            INNER JOIN
                users_pr u ON b.user_id = u.user_id
            WHERE
                b.booking_id = :booking_id
        ";

            $stmt = $conn->prepare($sql);
            $stmt->execute(['booking_id' => $bookingId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("BookingDetailsModel::getBookingDetailsById Error: " . $e->getMessage());
            throw $e;
        }
    }
    public function updateBookingStatus($bookingId, $status)
    {
        if (!$bookingId) {
            throw new Exception("Invalid booking ID provided.");
        }

        try {
            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }

            $sql = "
                UPDATE bookings_pr
                SET status = :status
                WHERE booking_id = :booking_id
            ";

            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                'booking_id' => $bookingId,
                'status' => $status
            ]);

            return $result;
        } catch (Exception $e) {
            error_log("BookingDetailsModel::updateBookingStatus Error: " . $e->getMessage());
            throw $e;
        }
    }
}
