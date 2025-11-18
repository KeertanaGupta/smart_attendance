<?php
// 1. Include our database "plug"
require_once 'db_connect.php';

// 2. Get the session ID from the URL (e.g., ...?session_id=1)
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$students = [];

if ($session_id > 0) {
    // 3. Get all students for this session, ordering by the newest first
    $sql = "SELECT student_id, student_name 
            FROM attendance_logs 
            WHERE session_id = ? 
            ORDER BY log_time DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // 4. Put all the results into our $students array
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
}
$conn->close();

// 5. 🔥 This is the "API" part:
// Tell the browser we're sending JSON, not HTML
header('Content-Type: application/json');

// Take our PHP array and "encode" it as a JSON string
echo json_encode($students);
?>