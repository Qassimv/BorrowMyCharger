<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

require_once('models/Database.php');

// Function to convert data to CSV
function arrayToCSV($data, $filename) {
    if (empty($data)) {
        die('No data to export');
    }

    // Get headers from first row
    $headers = array_keys($data[0]);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Get database connection
$db = Database::getInstance()->getdbConnection();

// Handle predefined reports
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $filename = '';
    $sql = '';
    
    switch ($type) {
        case 'all_charge_points':
            $filename = 'all_charge_points';
            $sql = "SELECT cp.*, u.username as owner_username 
                   FROM charge_points_pr cp 
                   JOIN users_pr u ON cp.user_id = u.user_id";
            break;
            
        case 'active_bookings':
            $filename = 'active_bookings';
            $sql = "SELECT b.*, u.username, cp.name as charge_point_name 
                   FROM bookings_pr b 
                   JOIN users_pr u ON b.user_id = u.user_id 
                   JOIN charge_points_pr cp ON b.charge_point_id = cp.charge_point_id 
                   WHERE b.status = 'Approved' 
                   ORDER BY b.created_at DESC";
            break;
            
        case 'user_stats':
            die('This report type is not available with the current database schema.');
            break;
            
        default:
            die('Invalid report type');
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        arrayToCSV($data, $filename);
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }
}
// Handle custom SQL query
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sqlQuery'])) {
    $sql = $_POST['sqlQuery'];
    $filename = isset($_POST['reportName']) ? $_POST['reportName'] : 'custom_report';
    
    // Basic SQL injection prevention
    if (stripos($sql, 'DROP') !== false || 
        stripos($sql, 'DELETE') !== false || 
        stripos($sql, 'UPDATE') !== false || 
        stripos($sql, 'INSERT') !== false) {
        die('Invalid query type. Only SELECT queries are allowed.');
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        arrayToCSV($data, $filename);
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }
} else {
    die('Invalid request');
} 