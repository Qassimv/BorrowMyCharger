<?php
require_once('Database.php');

class PaymentModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        if (!$this->db) {
            throw new Exception("Failed to initialize Database instance.");
        }
    }

    public function createPayment($user_id, $booking_id, $amount)
    {
        try {
            $conn = $this->db->getdbConnection();
            if (!$conn) {
                throw new Exception("Database connection failed.");
            }

            $stmt = $conn->prepare("INSERT INTO payments_pr (user_id, booking_id, amount, payment_status) 
                                    VALUES (:user_id, :booking_id, :amount, 'pending')");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Execute failed: " . $errorInfo[2]);
            }
        } catch (Exception $e) {
            error_log("PaymentModel::createPayment Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
