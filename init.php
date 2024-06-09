<?php
// Define database connection variables
$servername = "localhost"; // Change this if your database is hosted elsewhere
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "res"; // Replace with your database name

// Create connection
$link = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
