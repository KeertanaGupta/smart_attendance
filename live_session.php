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

// 3. Get the session ID from the URL (e.g., ...?id=1)
$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($session_id == 0) {
    header("Location: dashboard.php");
    exit;
}

// Helper function to build a friendly name
function get_class_name($class) {
    if (!$class) return "Unknown Class";
    return $class['year'] . " Year - " . $class['branch'] . " - " . $class['subject'] . " (" . $class['section'] . ")";
}

// 4. Fetch the session details (PIN) and ALL Class details
// 🔥 FIX: We now select 'c.*' (all columns from classes) instead of just 'c.class_name'
$sql = "SELECT s.session_pin, c.* FROM sessions s
        JOIN classes c ON s.class_id = c.class_id
        WHERE s.session_id = ?";
        
$session_pin = "N/A";
$class_details = null; // Will hold all class info

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $session_pin = $row['session_pin'];
        $class_details = $row; // Store the entire row of class data
    } else {
        header("Location: dashboard.php");
        exit;
    }
    $stmt->close();
}
$conn->close();

// 5. Build the friendly name
$friendly_class_name = get_class_name($class_details);

// 6. This is the URL for the QR code (no changes here)
$host = $_SERVER['HTTP_HOST']; // Gets 'localhost'
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // Gets '/smart_attendance'
$qr_code_url = "http://" . $host . $path . "/checkin.php?session=" . $session_id;

// ----- End of "Brain" -----
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Session - <?php echo htmlspecialchars($friendly_class_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    <style>
        :root {
            --bg-cream: #FCF5EE;
            --accent-light: #FFC4C4;
            --accent-medium: #EE6983;
            --text-dark: #850E35;
            --card-bg: #ffffff;
            --border-color: #f0e6e6;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: var(--bg-cream);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header-bar {
            background-color: var(--card-bg);
            padding: 1rem 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-bar .class-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .header-bar .end-session-btn {
            background-color: #D90429; /* A strong "stop" color */
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .header-bar .end-session-btn:hover {
            background-color: #850E35;
        }
        
        .live-container {
            display: flex;
            flex: 1; /* Makes this area grow to fill the space */
            padding: 2rem;
            gap: 2rem;
        }
        
        /* === Left Side (The QR/PIN) === */
        .qr-panel {
            flex: 1;
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            border-top: 5px solid var(--accent-medium);
        }
        .qr-panel h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--accent-medium);
        }
        .qr-panel p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        /* This is where the QR code will be drawn */
        .qr-code-box {
            background-color: #fdfdfd;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
        }
        #qr-code {
            width: 300px;
            height: 300px;
        }
        
        .pin-box {
            margin-top: 2rem;
            text-align: center;
        }
        .pin-box p {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .pin-box .pin-display {
            font-size: 5rem;
            font-weight: 700;
            letter-spacing: 0.5rem;
            color: var(--text-dark);
            background-color: var(--bg-cream);
            padding: 1rem 2rem;
            border-radius: 12px;
        }
        
        /* === Right Side (The Live List) === */
        .student-panel {
            flex: 1;
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }
        .student-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .student-panel-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }
        #student-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-medium);
        }
        
        .student-list {
            flex: 1; /* Makes the list fill the vertical space */
            overflow-y: auto; /* Adds a scrollbar if the list gets too long */
        }
        .student-list ol {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .student-list li {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 1.1rem;
            font-weight: 500;
        }
        .header-bar .header-buttons {
            display: flex;
            gap: 1rem;
        }
        .header-bar .download-btn {
            font-family: 'Poppins', sans-serif;
            background-color: #007bff; /* A nice blue */
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .header-bar .download-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <header class="header-bar">
    <div class="class-title">
        Live Session: <strong><?php echo htmlspecialchars($friendly_class_name); ?></strong>
    </div>

    <div class="header-buttons">
        <a href="download_list.php?id=<?php echo $session_id; ?>" class="download-btn">
            Download List
        </a>
        <a href="end_session.php?id=<?php echo $session_id; ?>" class="end-session-btn">
            End Session
        </a>
    </div>
</header>

    <div class="live-container">
    
        <div class="qr-panel">
            <h2>Scan to Check In</h2>
            <p>Students must scan this code and enter the PIN.</p>
            <div class="qr-code-box">
                <canvas id="qr-code"></canvas>
            </div>
            <div class="pin-box">
                <p>Enter this PIN:</p>
                <div class="pin-display"><?php echo htmlspecialchars($session_pin); ?></div>
            </div>
        </div>
        
        <div class="student-panel">
            <div class="student-panel-header">
                <h2>Checked In</h2>
                <div id="student-count">0</div>
            </div>
            <div class="student-list">
                <ol id="live-student-list">
                    </ol>
            </div>
        </div>
        
    </div>
    
    <script>
        // --- 1. Generate the QR Code ---
        
        // This is the URL we created in our PHP "Brain"
        const qrUrl = "<?php echo $qr_code_url; ?>";
        
        new QRious({
            element: document.getElementById('qr-code'),
            value: qrUrl,
            size: 300
        });

        
        // --- 2. The Live "Heartbeat" (AJAX) ---
        
        // JAVASCRIPT/AJAX TEACHING MOMENT:
        // AJAX (Asynchronous JavaScript and XML) is a way for a webpage
        // to ask the server for new data *without* reloading the page.
        // We will use the modern 'fetch' API to do this.
        
        const sessionId = <?php echo $session_id; ?>;
        const studentListElement = document.getElementById('live-student-list');
        const studentCountElement = document.getElementById('student-count');
        
        // This function will go to the server and get the list
        async function fetchStudentList() {
            try {
                // 'await' pauses the function until the server responds
                // This is our new "API" file we still need to build
                const response = await fetch('get_student_list.php?session_id=' + sessionId);
                
                // We'll get the data back as JSON (a data format)
                const students = await response.json();
                
                // Clear the old list
                studentListElement.innerHTML = '';
                
                // Update the count
                studentCountElement.textContent = students.length;
                
                // Loop through the new list and add each student
                students.forEach(student => {
                    const li = document.createElement('li');
                    li.textContent = `${student.student_name} (${student.student_id})`;
                    studentListElement.appendChild(li);
                });
                
            } catch (error) {
                console.error('Error fetching student list:', error);
            }
        }
        
        // Run the function for the first time
        fetchStudentList();
        
        // And then, run the function again every 5 seconds (5000 milliseconds)
        // This is our "heartbeat"
        setInterval(fetchStudentList, 5000);
        
        // --- 3. The Regenerating PIN "Heartbeat" ---

const pinDisplayElement = document.querySelector('.pin-display');

async function fetchNewPin() {
    try {
        // This calls our new file
        const response = await fetch('get_new_pin.php?id=' + sessionId);
        const newPin = await response.text();

        // Update the PIN on the screen
        pinDisplayElement.textContent = newPin;

    } catch (error) {
        console.error('Error fetching new PIN:', error);
    }
}

// Run this function every 30 seconds (30000 milliseconds)
setInterval(fetchNewPin, 30000);
    </script>
    
</body>
</html>