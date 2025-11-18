<?php
session_start();
require_once 'db_connect.php';

// Check if professor is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit;
}

$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Generate new PIN
$new_pin = random_int(1000, 9999);

// Update it in the database
$sql_update = "UPDATE sessions SET session_pin = ? WHERE session_id = ?";
if($stmt_update = $conn->prepare($sql_update)) {
    $stmt_update->bind_param("si", $new_pin, $session_id);
    $stmt_update->execute();
    $stmt_update->close();
}

// Return the new PIN as plain text
echo $new_pin;
?>