<?php
// 1. Always start the session to access it
session_start();

// 2. Unset all session variables (like a "factory reset")
$_SESSION = array();

// 3. Destroy the session itself (logs them out)
session_destroy();

// 4. Send them back to the login page
header("Location: login.php");
exit;
?>