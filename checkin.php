<?php
// ----- The "Brain" (PHP Logic) -----

// 🔥 FIX: We MUST start a session to track the browser
session_start();

// 1. Include our database "plug"
require_once 'db_connect.php';

// 2. Get the session ID from the URL
$session_id = isset($_GET['session']) ? intval($_GET['session']) : 0;

// 3. Create variables to hold messages
$error_msg = "";
$success_msg = "";
$friendly_class_name = "Attendance"; 

// Helper function
function get_class_name($class) {
    if (!$class) return "Unknown Class";
    return $class['year'] . " Year - " . $class['branch'] . " - " . $class['subject'] . " (" . $class['section'] . ")";
}

// 4. 🔥 NEW PROXY FIX: Check if this browser has already checked in
// We create a unique session variable for this specific session
$session_check_key = 'checked_in_' . $session_id;

if (isset($_SESSION[$session_check_key]) && $_SESSION[$session_check_key] === true) {
    $success_msg = "This browser has already been used to check in for this session. You may close this page.";
}


// 5. Get the Class Name (only if we don't have a success message)
if (empty($success_msg)) {
    if ($session_id > 0) {
        $sql_class = "SELECT c.* FROM classes c 
                      JOIN sessions s ON c.class_id = s.class_id 
                      WHERE s.session_id = ?";
                      
        if($stmt_class = $conn->prepare($sql_class)) {
            $stmt_class->bind_param("i", $session_id);
            $stmt_class->execute();
            $result_class = $stmt_class->get_result();
            
            if($row_class = $result_class->fetch_assoc()) {
                $friendly_class_name = get_class_name($row_class);
            }
            $stmt_class->close();
        }
    } else {
        $error_msg = "This is an invalid or expired attendance link.";
    }
}


// 6. Check if the student submitted the form (and if this browser isn't already used)
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($success_msg) && empty($error_msg)) {
    
    // 7. Get all the data from the form
    $student_id = trim($_POST['student_id']);
    $student_name = trim($_POST['student_name']);
    $session_pin = trim($_POST['session_pin']);
    
    if (empty($student_id) || empty($student_name) || empty($session_pin)) {
        $error_msg = "All fields are required.";
    } else {
        // 8. Verify the PIN is correct for this session
        $sql_verify = "SELECT 1 FROM sessions WHERE session_id = ? AND session_pin = ? AND is_active = TRUE";
        
        if ($stmt_verify = $conn->prepare($sql_verify)) {
            $stmt_verify->bind_param("is", $session_id, $session_pin);
            $stmt_verify->execute();
            $result_verify = $stmt_verify->get_result();
            
            if ($result_verify->num_rows == 1) {
                // PIN is correct! Now, check if student already checked in.
                $sql_check = "SELECT 1 FROM attendance_logs WHERE session_id = ? AND student_id = ?";
                if($stmt_check = $conn->prepare($sql_check)) {
                    $stmt_check->bind_param("is", $session_id, $student_id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if($result_check->num_rows > 0) {
                        $error_msg = "This Student ID (" . htmlspecialchars($student_id) . ") has already checked in.";
                    } else {
                        // 9. ALL CLEAR! Insert the student into the log
                        $sql_insert = "INSERT INTO attendance_logs (session_id, student_id, student_name) VALUES (?, ?, ?)";
                        if($stmt_insert = $conn->prepare($sql_insert)) {
                            $stmt_insert->bind_param("iss", $session_id, $student_id, $student_name);
                            if($stmt_insert->execute()) {
                                $success_msg = "Success! You are now checked in. You may close this page.";
                                
                                // 10. 🔥 SET THE FLAG! Mark this browser as "used" for this session.
                                $_SESSION[$session_check_key] = true;
                                
                            } else {
                                $error_msg = "An error occurred. Please try again.";
                            }
                            $stmt_insert->close();
                        }
                    }
                    $stmt_check->close();
                }
            } else {
                $error_msg = "Invalid PIN. Please check the projector and try again.";
            }
            $stmt_verify->close();
        }
    }
}
$conn->close();

// ----- End of "Brain" -----
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($friendly_class_name); ?> Check-in</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-cream: #FCF5EE;
            --accent-light: #FFC4C4;
            --accent-medium: #EE6983;
            --text-dark: #850E35;
            --error-red: #D90429;
            --success-green: #008000;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-cream);
        }
        
        .checkin-container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            width: 100%;
            max-width: 450px;
            margin: 1rem;
            border-top: 5px solid var(--accent-medium);
            text-align: center;
        }
        
        .checkin-container h1 { color: var(--text-dark); font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
        .checkin-container p { color: var(--accent-medium); font-size: 1rem; margin-bottom: 2rem; }
        .input-group { margin-bottom: 1.5rem; text-align: left; }
        .input-group label { display: block; margin-bottom: 0.5rem; color: var(--text-dark); font-weight: 500; }
        .input-group input { width: 100%; padding: 0.75rem 1rem; box-sizing: border-box; border: 2px solid var(--accent-light); border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 1rem; }
        .btn-checkin { width: 100%; padding: 0.85rem; border: none; border-radius: 8px; background-image: linear-gradient(90deg, var(--accent-medium) 0%, #d15870 100%); color: #ffffff; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn-checkin:hover { box-shadow: 0 5px 15px rgba(238, 105, 131, 0.4); }
        .message { padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-weight: 500; display: block; }
        .error { background-color: #fde8e8; color: var(--error-red); border: 1px solid var(--error-red); }
        .success { background-color: #e8f5e9; color: var(--success-green); border: 1px solid var(--success-green); }
    </style>
</head>
<body>

    <div class="checkin-container">
        <h1><?php echo htmlspecialchars($friendly_class_name); ?></h1>
        <p>Enter your details to check in.</p>
        
        <?php
        // This PHP block will display EITHER the error or success message
        if (!empty($error_msg)) {
            echo '<div class="message error">' . $error_msg . '</div>';
        }
        if (!empty($success_msg)) {
            echo '<div class="message success">' . $success_msg . '</div>';
        }
        ?>
        
        <?php
        // 🔥 UX FIX: If there is *any* success message (either from the session
        // check or the POST), we HIDE the form.
        if (empty($success_msg)):
        ?>
        
            <form action="checkin.php?session=<?php echo $session_id; ?>" method="POST">
                <div class="input-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" required>
                </div>
                <div class="input-group">
                    <label for="student_name">Full Name</label>
                    <input type="text" id="student_name" name="student_name" required>
                </div>
                <div class="input-group">
                    <label for="session_pin">4-Digit PIN (from screen)</label>
                    <input type="number" id="session_pin" name="session_pin" required>
                </div>
                <button type="submit" class="btn-checkin">Check In</button>
            </form>
            
        <?php endif; // End of the "if" statement ?>
        
    </div>

</body>
</html>