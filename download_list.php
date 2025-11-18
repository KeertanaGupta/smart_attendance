<?php
// Start session and check if professor is logged in
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

// Helper function to build a friendly name
function get_class_name($class) {
    if (!$class) return "Unknown Class";
    return $class['year'] . " Year - " . $class['branch'] . " - " . $class['subject'] . " (" . $class['section'] . ")";
}

// Get the session ID
$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($session_id == 0) {
    die("Invalid session.");
}

// Set up the file download headers
$filename = "attendance_session_" . $session_id . "_" . date("Y-m-d") . ".csv";

// These headers tell the browser to "download this file"
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Get Class/Session details for the file header
// We select c.* (all columns) to build the friendly name
$sql_info = "SELECT c.*, s.created_at 
             FROM sessions s 
             JOIN classes c ON s.class_id = c.class_id 
             WHERE s.session_id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $session_id);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
$info = $result_info->fetch_assoc();
$friendly_class_name = get_class_name($info); // Use the helper

// Get the student list
$sql_logs = "SELECT student_id, student_name, log_time 
             FROM attendance_logs 
             WHERE session_id = ? 
             ORDER BY log_time ASC";
$stmt_logs = $conn->prepare($sql_logs);
$stmt_logs->bind_param("i", $session_id);
$stmt_logs->execute();
$result_logs = $stmt_logs->get_result();

// "Print" the CSV data to the browser
$output = fopen('php://output', 'w');

// Add our headers to the CSV
fputcsv($output, ['Class:', $friendly_class_name]);
fputcsv($output, ['Date:', date('F j, Y', strtotime($info['created_at']))]);
fputcsv($output, []); // Blank row
fputcsv($output, ['Student ID', 'Student Name', 'Check-in Time']);

// Loop through the students and add them
while ($row = $result_logs->fetch_assoc()) {
    fputcsv($output, [$row['student_id'], $row['student_name'], $row['log_time']]);
}

fclose($output);
$conn->close();
exit;
?>