<?php
include 'init.php';
session_start();

// Get the project ID from the session
$projectId = $_SESSION["p_id"];

// Update the project status to 1
$updateQuery = "UPDATE project SET state = 1 WHERE id = $projectId";
$result = mysqli_query($link, $updateQuery);

if ($result) {
    // Redirect back to the project display page after successful update
    header("Location: projects.php");
    exit();
} else {
    // Handle error
    echo "Error updating project status.";
}
?>
