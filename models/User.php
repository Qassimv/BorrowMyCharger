<!-- user.php -->

<?php

require_once 'Database.php';

class User
{
    // Database connection
    private $db;

    // User properties
    public $user_id;
    public $username;
    public $passHash;
    public $full_name;
    public $role;
    public $is_approved;
    public $created_at;

    // Constructor to initialize the database connection
    public function __construct()
    {
        $this->db = Database::getInstance()->getdbConnection();
    }

    // Method to register a new user
    public function register($username, $password, $full_name, $role)
    {
        // Validate full name length (between 5 and 20 characters)
        if (strlen($full_name) < 4 || strlen($full_name) > 20) {
            return "Full name must be between 4 and 20 characters.";
        }

        // Username can either be alphanumeric or an email
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            // Valid email format, proceed with email validation
            $isEmail = true;
        } elseif (!preg_match("/^[a-zA-Z0-9]{4,20}$/", $username)) {  // Changed to allow 4-20 characters
            return "Username must be alphanumeric and between 4-20 characters. Or an Email.";
        } else {
            $isEmail = false;
        }

        // Password validation: at least one uppercase, one lowercase, one number, one special char, and minimum 8 characters
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
            return "Password must be at least 8 characters, include at least one uppercase letter, one lowercase letter, one number, and one special character.";
        }

        // Check if username (email or alphanumeric) already exists
        $sql = "SELECT * FROM users_pr WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return "Username or email already exists. Please choose another one.";
        }

        // Hash the password
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $sql = "INSERT INTO users_pr (username, passHash, full_name, role, is_approved) 
        VALUES (:username, :passHash, :full_name, :role, 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':passHash', $passHash);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            return true; // Registration successful
        } else {
            return "Registration failed. Please try again.";
        }
    }


    // Method to fetch a user by their username (for login)
    public function getUserByUsername($username)
    {
        $sql = "SELECT * FROM users_pr WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);  // Return user data or false if not found
    }
    // Fetch user by email
    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users_pr WHERE username = :email"; // Assuming email is stored in 'username' column
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);  // Return user data or false if not found
    }

    // Method to fetch all users (for admin purposes)
    public function getAllUsers()
    {
        $sql = "SELECT * FROM users_pr";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Return all users data
    }

    // Method to fetch a user by user_id
    public function getUserById($userId)
    {
        $sql = "SELECT * FROM users_pr WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);  // Return user data or false if not found
    }
    // Method to approve a user
    public function approveUser($user_id)
    {
        $sql = "UPDATE users_pr SET is_approved = 1 WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Method to suspend a user
    public function suspendUser($user_id)
    {
        $sql = "UPDATE users_pr SET is_approved = 0 WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Method to delete a user
    // Method to delete a user (ignoring foreign key constraints)
    public function deleteUser($user_id)
    {
        try {
            // Disable foreign key checks temporarily
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Prepare SQL statement to delete the user
            $sql = "DELETE FROM users_pr WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);

            // Execute the delete query
            $result = $stmt->execute();

            // Re-enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");

            return $result;
        } catch (PDOException $e) {
            // Handle any exceptions
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
}
