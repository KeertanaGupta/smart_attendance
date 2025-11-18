<?php
/*
 * create_hash.php
 * This file will generate a new, perfect hash for "password123"
 * using YOUR version of PHP.
 */

// The password we want to hash
$password_to_hash = "password123";

// Hash the password
$new_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);

// Display it on the screen
echo "Your new, perfect hash is: <br><br>";
echo "<strong style='font-size: 1.2rem; background: #eee; padding: 10px;'>" . $new_hash . "</strong>";
echo "<br><br>Please copy this ENTIRE string (starting with $2y$) and paste it into phpMyAdmin.";
?>