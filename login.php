<?php

session_start();
require_once 'db_connect.php';
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT prof_id, prof_name, password_hash FROM professors WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['prof_id'] = $row['prof_id'];
            $_SESSION['prof_name'] = $row['prof_name'];

            header("Location: dashboard.php");
            exit; 

        } else {
            $error_msg = "Invalid email or password.";
        }
    } else {
        $error_msg = "Invalid email or password.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Login - Smart Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Your Color Palette */
            --bg-cream: #FCF5EE;
            --accent-light: #FFC4C4;
            --accent-medium: #EE6983;
            --text-dark: #850E35;
            --error-red: #D90429; /* For error messages */
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-cream);
            background-image: linear-gradient(135deg, var(--bg-cream) 0%, #fff0e6 100%);
        }

        .login-container {
            background-color: #ffffff;
            padding: 2.5rem 3rem;
            border-radius: 16px;
            /* This shadow makes it "float" - great UI */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            width: 100%;
            max-width: 400px;
            border-top: 5px solid var(--accent-medium);
            text-align: center;
        }

        .login-container h1 {
            color: var(--text-dark);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-container p {
            color: var(--accent-medium);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            box-sizing: border-box; /* Important for padding to work right */
            border: 2px solid var(--accent-light);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            /* This is a great UX touch */
            border-color: var(--accent-medium);
            box-shadow: 0 0 10px var(--accent-light);
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: 8px;
            background-color: var(--accent-medium);
            /* Your gradient idea */
            background-image: linear-gradient(90deg, var(--accent-medium) 0%, #d15870 100%);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            /* On hover, we make it "pop" */
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(238, 105, 131, 0.4);
        }

        /* This is our new Error Message styling */
        .error-message {
            background-color: #fde8e8;
            color: var(--error-red);
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--error-red);
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            display: block; /* It will be hidden by default */
        }

    </style>
</head>
<body>

    <div class="login-container">
        <h1>Welcome Back</h1>
        <p>Login to start your attendance session</p>

        <?php
        if (!empty($error_msg)) {
            echo '<div class="error-message">' . $error_msg . '</div>';
        }
        ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>

</body>
</html>