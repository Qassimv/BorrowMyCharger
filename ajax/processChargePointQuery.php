<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

require_once('../models/Database.php');

// Function to validate SQL query
function validateQuery($query) {
    // Convert to lowercase for checking
    $query = strtolower($query);
    
    // Only allow SELECT queries
    if (!preg_match('/^\s*select\s+/i', $query)) {
        return false;
    }
    
    // Check for dangerous operations
    $dangerous = ['delete', 'drop', 'update', 'insert', 'alter', 'truncate', 'create', 'replace'];
    foreach ($dangerous as $operation) {
        if (strpos($query, $operation) !== false) {
            return false;
        }
    }
    
    // Check if query is related to charge points
    if (strpos($query, 'charge_points_pr') === false) {
        return false;
    }
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    
    if (!validateQuery($query)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid query. Only SELECT queries on charge_points_pr table are allowed.']);
        exit;
    }
    
    try {
        $db = Database::getInstance()->getdbConnection();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Store results in session for backward compatibility
        $_SESSION['queryResults'] = $results;
        $_SESSION['lastQuery'] = $query;
        
        // Return results as JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Query error: ' . $e->getMessage()]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}