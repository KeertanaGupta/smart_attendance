<?php
// 1. Start session and check login
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';
$prof_id = $_SESSION['prof_id'];
$message = "";

// 2. Handle the "Add Class" form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $year = $_POST['year'];
    $branch = $_POST['branch'];
    $subject = $_POST['subject'];
    $section = $_POST['section'];
    
    $sql_insert = "INSERT INTO classes (prof_id, year, branch, subject, section) VALUES (?, ?, ?, ?, ?)";
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        $stmt_insert->bind_param("iisss", $prof_id, $year, $branch, $subject, $section);
        if ($stmt_insert->execute()) {
            $message = "Class added successfully!";
        } else {
            $message = "Error adding class.";
        }
        $stmt_insert->close();
    }
}

// 3. Handle a "Delete Class" request
if (isset($_GET['delete'])) {
    $class_id_to_delete = intval($_GET['delete']);
    
    // 🔥 FIX: We use a transaction. All queries must succeed, or none will.
    $conn->begin_transaction();
    
    try {
        // 1. Delete "grandchildren": all attendance logs for all sessions of this class
        $sql_del_logs = "DELETE FROM attendance_logs WHERE session_id IN (SELECT session_id FROM sessions WHERE class_id = ?)";
        $stmt_del_logs = $conn->prepare($sql_del_logs);
        $stmt_del_logs->bind_param("i", $class_id_to_delete);
        $stmt_del_logs->execute();
        $stmt_del_logs->close();
        
        // 2. Delete "children": all sessions for this class
        $sql_del_sessions = "DELETE FROM sessions WHERE class_id = ?";
        $stmt_del_sessions = $conn->prepare($sql_del_sessions);
        $stmt_del_sessions->bind_param("i", $class_id_to_delete);
        $stmt_del_sessions->execute();
        $stmt_del_sessions->close();
        
        // 3. Delete the "parent": the class itself (with security check)
        $sql_del_class = "DELETE FROM classes WHERE class_id = ? AND prof_id = ?";
        $stmt_del_class = $conn->prepare($sql_del_class);
        $stmt_del_class->bind_param("ii", $class_id_to_delete, $prof_id);
        $stmt_del_class->execute();
        
        // Check if the class was actually deleted (it might not belong to this prof)
        if ($stmt_del_class->affected_rows > 0) {
            $conn->commit(); // All good, save changes
            $message = "Class and all associated data deleted successfully!";
        } else {
            // The class didn't belong to this prof, so nothing was deleted.
            throw new Exception("Class not found or you do not have permission to delete it.");
        }
        $stmt_del_class->close();
        
    } catch (Exception $e) {
        // Something went wrong, undo everything
        $conn->rollback();
        $message = "Error deleting class. (This class may still be in use).";
    }
}

// 4. Fetch all existing classes for this professor to display
$classes_list = [];
$sql_fetch = "SELECT * FROM classes WHERE prof_id = ?";
if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $prof_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    while ($row = $result->fetch_assoc()) {
        $classes_list[] = $row;
    }
    $stmt_fetch->close();
}
$conn->close();

// Helper function to build a friendly name
function get_class_name($class) {
    return $class['year'] . " Year - " . $class['branch'] . " - " . $class['subject'] . " (" . $class['section'] . ")";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-cream: #FCF5EE;
            --accent-light: #FFC4C4;
            --accent-medium: #EE6983;
            --text-dark: #850E35;
            --card-bg: #ffffff;
            --border-color: #f0e6e6;
        }
        body { font-family: 'Poppins', sans-serif; margin: 0; background-color: var(--bg-cream); color: var(--text-dark); }
        .header-bar { background-color: var(--card-bg); padding: 1rem 2rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--accent-medium); }
        .header-bar .logo { font-size: 1.5rem; font-weight: 700; color: var(--text-dark); }
        .header-bar a { font-family: 'Poppins', sans-serif; background-color: var(--accent-light); color: var(--text-dark); padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 500; cursor: pointer; text-decoration: none; transition: all 0.3s ease; }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 2rem; }
        .card { background-color: var(--card-bg); border-radius: 16px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); margin-bottom: 2rem; }
        .card h2 { font-size: 1.8rem; font-weight: 600; margin-top: 0; margin-bottom: 1.5rem; }
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .input-group input, .input-group select { width: 100%; padding: 0.75rem 1rem; box-sizing: border-box; border: 2px solid var(--accent-light); border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 1rem; background-color: #fff; color: var(--text-dark); }
        .btn-submit { width: 100%; padding: 0.85rem; border: none; border-radius: 8px; background-image: linear-gradient(90deg, var(--accent-medium) 0%, #d15870 100%); color: #ffffff; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        .message { padding: 1rem; background: #e8f5e9; color: #008000; border-radius: 8px; margin-bottom: 1rem; }
        .class-list-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); }
        .class-list-item:last-child { border: 0; }
        .delete-btn { color: #D90429; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

    <header class="header-bar">
        <div class="logo">SmartAttendance</div>
        <a href="dashboard.php">Back to Dashboard</a>
    </header>

    <div class="container">
        <?php if (!empty($message)) echo '<div class="message">' . $message . '</div>'; ?>

        <div class="card">
            <h2>Add New Class</h2>
            <form action="manage_classes.php" method="POST">
                <div class="input-group">
                    <label for="year">Year</label>
                    <select id="year" name="year" required>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="branch">Branch (e.g., CSE, IT, MECH)</label>
                    <input type="text" id="branch" name="branch" required>
                </div>
                <div class="input-group">
                    <label for="subject">Subject (e.g., IWT, DBMS)</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="input-group">
                    <label for="section">Section (e.g., A, B, A1)</label>
                    <input type="text" id="section" name="section" required>
                </div>
                <button type="submit" name="add_class" class="btn-submit">Add Class</button>
            </form>
        </div>

        <div class="card">
            <h2>Your Classes</h2>
            <div class="class-list">
                <?php if (empty($classes_list)): ?>
                    <p>You have not added any classes yet.</p>
                <?php else: ?>
                    <?php foreach ($classes_list as $class): ?>
                        <div class="class-list-item">
                            <span><?php echo htmlspecialchars(get_class_name($class)); ?></span>
                            <a href="manage_classes.php?delete=<?php echo $class['class_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure? This will delete all attendance logs for this class.');">Delete</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>