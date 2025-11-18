<?php
// ----- The "Brain" (PHP Logic) -----

// 1. We MUST start the session to access the $_SESSION variables
session_start();

// 2. 🔥 SECURITY CHECK
// Check if the user is logged in. If not, redirect them to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// 3. Include our database "plug"
require_once 'db_connect.php';

// 4. Get the professor's name from the session to welcome them
$prof_name = $_SESSION['prof_name'];
$prof_id = $_SESSION['prof_id'];

// Helper function to build a friendly name (must match in all files)
function get_class_name($class) {
    return $class['year'] . " Year - " . $class['branch'] . " - " . $class['subject'] . " (" . $class['section'] . ")";
}

// 5. 🔥 FIX: Fetch the list of classes for this professor
// We select all columns ('*') so we can build the friendly name.
$classes_list = [];
$sql = "SELECT * FROM classes WHERE prof_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $prof_id); // "i" means we are binding an "integer"
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Loop through the results and store them in an array
    while ($row = $result->fetch_assoc()) {
        $classes_list[] = $row;
    }
    $stmt->close();
}
$conn->close();

// ----- End of "Brain" -----
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-cream: #FCF5EE;
            --accent-light: #FFC4C4;
            --accent-medium: #EE6983;
            --text-dark: #850E35;
            --card-bg: #ffffff;
            --border-color: #f0e6e6;
            --blue: #007bff;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: var(--bg-cream);
            color: var(--text-dark);
        }
        
        /* === Main Header Bar === */
        .header-bar {
            background-color: var(--card-bg);
            padding: 1rem 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--accent-medium);
        }
        .header-bar .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .header-bar .logout-btn {
            font-family: 'Poppins', sans-serif;
            background-color: var(--accent-medium);
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .header-bar .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(238, 105, 131, 0.4);
        }
        
        /* === Main Content Area === */
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome-header {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .welcome-sub {
            font-size: 1.2rem;
            color: var(--accent-medium);
            margin-bottom: 2.5rem;
        }
        
        /* === The "Start Session" Card === */
        .session-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        }
        .session-card h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .input-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            box-sizing: border-box; 
            border: 2px solid var(--accent-light);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            background-color: #fff;
            color: var(--text-dark);
        }
        
        .btn-start {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: 8px;
            background-image: linear-gradient(90deg, var(--accent-medium) 0%, #d15870 100%);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(238, 105, 131, 0.4);
        }

        /* Style for the new Manage Classes button */
        .manage-classes-btn {
            font-family: 'Poppins', sans-serif;
            background-color: var(--blue);
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .manage-classes-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <header class="header-bar">
        <div class="logo">SmartAttendance</div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <div class="container">
        <h1 class="welcome-header">Welcome, <?php echo htmlspecialchars($prof_name); ?>!</h1>
        <p class="welcome-sub">Ready to start your next session?</p>
        
        <div style="margin-bottom: 2rem; text-align: right;">
            <a href="manage_classes.php" class="manage-classes-btn">Manage Your Classes</a>
        </div>

        <div class="session-card">
            <h2>Start a New Session</h2>
            
            <form action="create_session.php" method="POST">
                <div class="input-group">
                    <label for="class-select">Choose a class:</label>
                    <select id="class-select" name="class_id" required>
                        
                        <?php
                        // 🔥 PHP FIX:
                        // We use PHP to loop through the $classes_list array
                        // and create an <option> for each class,
                        // building the name with our helper function.
                        
                        if (empty($classes_list)) {
                            echo '<option disabled>No classes found. Please add a class.</option>';
                        } else {
                            foreach ($classes_list as $class) {
                                $friendly_name = get_class_name($class);
                                echo '<option value="' . htmlspecialchars($class['class_id']) . '">' 
                                     . htmlspecialchars($friendly_name) 
                                     . '</option>';
                            }
                        }
                        ?>
                        
                    </select>
                </div>
                <button type="submit" class="btn-start">Start New Session</button>
            </form>
        </div>
    </div>

</body>
</html>