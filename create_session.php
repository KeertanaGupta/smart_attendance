<?php
// ----- The "Brain" (PHP Logic) -----

// 1. Start the session and check if logged in
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// 2. Include our database "plug"
require_once 'db_connect.php';

// 3. Check if the form was submitted and we got a class_id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['class_id'])) {

    $class_id = $_POST['class_id'];

    // 4. The "Star" Feature: Generate a random, 4-digit PIN
    // We'll use random_int for a secure random number.
    $session_pin = random_int(1000, 9999); 

    // 5. Prepare to insert this new session into the database
    $sql = "INSERT INTO sessions (class_id, session_pin, is_active) VALUES (?, ?, TRUE)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $class_id, $session_pin); // "i" for integer, "s" for string (PIN)

        // 6. Run the query
        if ($stmt->execute()) {
            // Success! The session is created.

            // 7. Get the 'session_id' of the row we JUST inserted
            $new_session_id = $conn->insert_id;

            // 8. Redirect the professor to the live page, passing this new ID
            header("Location: live_session.php?id=" . $new_session_id);
            exit;

        } else {
            echo "Error: Could not create session.";
        }
        $stmt->close();
    }
} else {
    // If someone tries to access this page directly, send them to the dashboard
    header("Location: dashboard.php");
    exit;
}

$conn->close();
?>