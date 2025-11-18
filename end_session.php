<?php
// 1. Start session and check if professor is logged in
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

// 2. Get the session ID from the URL
$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$prof_id = $_SESSION['prof_id'];

if ($session_id > 0) {
    
    // 3. 🔥 SQL to "deactivate" the session
    // We also check that the prof_id matches, so one professor
    // can't accidentally close another's session.
    $sql = "UPDATE sessions s
            JOIN classes c ON s.class_id = c.class_id
            SET s.is_active = FALSE
            WHERE s.session_id = ? AND c.prof_id = ?";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $session_id, $prof_id);
        $stmt->execute();
        $stmt->close();
    }
}

// 4. Send them back to the dashboard
header("Location: dashboard.php");
exit;
?>