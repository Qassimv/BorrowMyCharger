<?php

require_once 'Database.php';

class ChargePoint
{
    // Database connection
    private $db;

    // Charge point properties
    public $charge_point_id;
    public $user_id;
    public $name;
    public $address;
    public $postcode;
    public $latitude;
    public $longitude;
    public $price_per_kwh;
    public $available_from;
    public $available_to;
    public $isAvailable;
    public $image_path;
    public $status;
    public $created_at;
    public $charger_type; // New property for charger type

    // Constructor to initialize the database connection
    public function __construct()
    {
        $this->db = Database::getInstance()->getdbConnection();
    }

    // Method to add a new charge point
    public function addChargePoint($data, $user_id)
    {
        // Check if user already has a charge point
        if ($this->userHasChargePoint($user_id)) {
            return "You already have a charging point. You can only have one charging point per account.";
        }

        // Set default values for coordinates if not provided
        if (empty($data['latitude'])) {
            $data['latitude'] = 0; // Default latitude
        }
        if (empty($data['longitude'])) {
            $data['longitude'] = 0; // Default longitude
        }

        // Prepare the SQL statement
        $sql = "INSERT INTO charge_points_pr (
                user_id, 
                name, 
                address, 
                postcode, 
                latitude, 
                longitude, 
                price_per_kwh, 
                available_from, 
                available_to, 
                isAvailable, 
                image_path, 
                status,
                charger_type
            ) VALUES (
                :user_id, 
                :name, 
                :address, 
                :postcode, 
                :latitude, 
                :longitude, 
                :price_per_kwh, 
                :available_from, 
                :available_to, 
                :isAvailable, 
                :image_path,
                'Approved',
                :charger_type
            )";

        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':postcode', $data['postcode']);
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':price_per_kwh', $data['price_per_kwh']);
        $stmt->bindParam(':available_from', $data['available_from']);
        $stmt->bindParam(':available_to', $data['available_to']);
        $isAvailable = $data['isAvailable'] === 'Available' ? 1 : 0;
        $stmt->bindParam(':isAvailable', $isAvailable);
        $stmt->bindParam(':image_path', $data['image_path']);
        $stmt->bindParam(':charger_type', $data['charger_type']);

        try {
            // Execute the query
            if ($stmt->execute()) {
                return true; // Charge point added successfully
            } else {
                return "Failed to add charging point. Please try again.";
            }
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // Method to check if a user already has a charge point
    public function userHasChargePoint($user_id)
    {
        $sql = "SELECT COUNT(*) FROM charge_points_pr WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // Method to get a user's charge point
    public function getUserChargePoint($user_id)
    {
        $sql = "SELECT * FROM charge_points_pr WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Method to update a charge point
    public function updateChargePoint($data, $charge_point_id, $user_id)
    {
        // Check if the charge point belongs to the user
        if (!$this->isChargePointOwner($charge_point_id, $user_id)) {
            return "You don't have permission to edit this charging point.";
        }

        // Prepare the SQL statement
        $sql = "UPDATE charge_points_pr SET 
                name = :name, 
                address = :address, 
                postcode = :postcode, 
                latitude = :latitude, 
                longitude = :longitude, 
                price_per_kwh = :price_per_kwh, 
                available_from = :available_from, 
                available_to = :available_to, 
                isAvailable = :isAvailable,
                charger_type = :charger_type";

        // If image is updated, add it to the query
        if (!empty($data['image_path'])) {
            $sql .= ", image_path = :image_path";
        }

        $sql .= " WHERE charge_point_id = :charge_point_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':postcode', $data['postcode']);
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':price_per_kwh', $data['price_per_kwh']);
        $stmt->bindParam(':available_from', $data['available_from']);
        $stmt->bindParam(':available_to', $data['available_to']);
        $isAvailable = $data['isAvailable'] === 'Available' ? 1 : 0;
        $stmt->bindParam(':isAvailable', $isAvailable);
        $stmt->bindParam(':charger_type', $data['charger_type']);
        
        // If image is updated, bind that parameter
        if (!empty($data['image_path'])) {
            $stmt->bindParam(':image_path', $data['image_path']);
        }
        
        $stmt->bindParam(':charge_point_id', $charge_point_id);
        $stmt->bindParam(':user_id', $user_id);

        try {
            // Execute the query
            if ($stmt->execute()) {
                return true; // Charge point updated successfully
            } else {
                return "Failed to update charging point. Please try again.";
            }
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // Method to delete a charge point
    public function deleteChargePoint($charge_point_id, $user_id = null, $isAdmin = false)
    {
        // If not admin, check if the charge point belongs to the user
        if (!$isAdmin && !$this->isChargePointOwner($charge_point_id, $user_id)) {
            return "You don't have permission to delete this charging point.";
        }

        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // First, check if there are any bookings for this charge point
            $sql = "SELECT COUNT(*) FROM bookings_pr WHERE charge_point_id = :charge_point_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':charge_point_id', $charge_point_id);
            $stmt->execute();
            $bookingCount = $stmt->fetchColumn();
            
            if ($bookingCount > 0) {
                // If bookings exist, return an error message
                $this->db->rollBack();
                return "Cannot delete this charging point because it has associated bookings. Please contact an administrator for assistance.";
            }
            
            // If no bookings exist, proceed with deletion
            $sql = "DELETE FROM charge_points_pr WHERE charge_point_id = :charge_point_id";
            if (!$isAdmin) {
                $sql .= " AND user_id = :user_id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':charge_point_id', $charge_point_id);
            if (!$isAdmin) {
                $stmt->bindParam(':user_id', $user_id);
            }
            $stmt->execute();
            
            // Commit transaction
            $this->db->commit();
            return true; // Charge point deleted successfully
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    // Method to check if a user is the owner of a charge point
    public function isChargePointOwner($charge_point_id, $user_id)
    {
        $sql = "SELECT COUNT(*) FROM charge_points_pr WHERE charge_point_id = :charge_point_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':charge_point_id', $charge_point_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // Method to get a charge point by ID
    public function getChargePointById($charge_point_id)
    {
        $sql = "SELECT * FROM charge_points_pr WHERE charge_point_id = :charge_point_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':charge_point_id', $charge_point_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Method to get all charge points (admin function)
    public function getAllChargePoints()
    {
        $sql = "SELECT cp.*, u.username 
                FROM charge_points_pr cp 
                LEFT JOIN users_pr u ON cp.user_id = u.user_id 
                ORDER BY cp.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Method to get all unique charger types
    public function getAllChargerTypes()
    {
        $sql = "SELECT DISTINCT charger_type FROM charge_points_pr WHERE isAvailable = 1 AND charger_type IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Method to get charge points by type
    public function getChargePointsByType($type)
    {
        $sql = "SELECT cp.*, u.username FROM charge_points_pr cp 
                JOIN users_pr u ON cp.user_id = u.user_id
                WHERE cp.isAvailable = 1 AND cp.charger_type = :type
                ORDER BY cp.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helper method to upload an image
    public function uploadImage($file) {
        $target_dir = "uploads/charge_points/";

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $fileName = basename($file["name"]);
        $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $imageFileType; // Generate a unique file name
        $target_file = $target_dir . $newFileName;

        // Debugging: Log the target file path
        error_log("Target file: " . $target_file);

        // Check if image file is a valid image
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            error_log("File is not an image.");
            return "File is not an image.";
        }

        // Check file size (limit to 5MB)
        if ($file["size"] > 5000000) {
            error_log("File is too large: " . $file["size"]);
            return "Sorry, your file is too large.";
        }

        // Allow certain file formats
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            error_log("Invalid file format: " . $imageFileType);
            return "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }

        // Try to upload the file
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            error_log("File uploaded successfully: " . $target_file);
            return $target_file; // Return the relative path to the uploaded file
        } else {
            error_log("Failed to move uploaded file.");
            return "Sorry, there was an error uploading your file.";
        }
    }

    // Method to update a charge point (admin version)
    public function updateChargePointAdmin($data, $charge_point_id) {
        try {
            $sql = "UPDATE charge_points_pr 
                    SET name = :name, address = :address, postcode = :postcode, latitude = :latitude, 
                        longitude = :longitude, price_per_kwh = :price_per_kwh, available_from = :available_from, 
                        available_to = :available_to, isAvailable = :isAvailable, charger_type = :charger_type, 
                        image_path = :image_path, status = :status 
                    WHERE charge_point_id = :charge_point_id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':postcode', $data['postcode']);
            $stmt->bindParam(':latitude', $data['latitude']);
            $stmt->bindParam(':longitude', $data['longitude']);
            $stmt->bindParam(':price_per_kwh', $data['price_per_kwh']);
            $stmt->bindParam(':available_from', $data['available_from']);
            $stmt->bindParam(':available_to', $data['available_to']);
            $stmt->bindParam(':isAvailable', $data['isAvailable'], PDO::PARAM_INT);
            $stmt->bindParam(':charger_type', $data['charger_type']);
            $stmt->bindParam(':image_path', $data['image_path']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':charge_point_id', $charge_point_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            return 'Database error: ' . $e->getMessage();
        }
    }
}