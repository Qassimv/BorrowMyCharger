<?php

require_once 'Database.php';

class ContactMessageModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function saveMessage($senderId, $recipientId, $subject, $messageText)
    {
        try {
            $conn = $this->db->getdbConnection();

            $sql = "INSERT INTO messages_pr (sender_id, recipient_id, subject, message_text, sent_at)
                    VALUES (:sender_id, :recipient_id, :subject, :message_text, NOW())";

            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'message_text' => $messageText
            ]);
        } catch (Exception $e) {
            error_log("ContactMessageModel::saveMessage Error: " . $e->getMessage());
            return false;
        }
    }
}
