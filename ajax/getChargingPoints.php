<?php
// Start session and disable error display in output
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Include database connection
    require_once('../models/Database.php');
    
    // Create database connection
    $db = Database::getInstance()->getdbConnection();
    
    // Prepare SQL query
    $sql = "SELECT cp.*, u.username FROM charge_points_pr cp 
            JOIN users_pr u ON cp.user_id = u.user_id
            WHERE cp.isAvailable = 1";
    
    // Add filter for type if provided
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        // Convert type parameter to match database value
        $typeMap = [
            'dc-fast-charger' => 'DC Fast Charger',
            'level-2-charger' => 'Level 2 Charger',
            'tesla-charger' => 'Tesla Charger'
        ];
        
        $type = isset($typeMap[$_GET['type']]) ? $typeMap[$_GET['type']] : $_GET['type'];
        $sql .= " AND cp.charger_type = :type";
    }
    
    // Add order by
    $sql .= " ORDER BY cp.created_at DESC";
    
    // Prepare and execute the statement
    $stmt = $db->prepare($sql);
    
    // Bind type parameter if provided
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $stmt->bindParam(':type', $type);
    }
    
    $stmt->execute();
    
    // Fetch all results
    $chargingPoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    echo json_encode($chargingPoints);
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode(['error' => $e->getMessage()]);
}