<?php
session_start(); // Start the session

// Destroy all session data (logout the user)
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session itself

// Redirect the user to the login page after logging out
header('Location: Login.php');
exit();
?>
