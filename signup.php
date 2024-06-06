<?php
// Include the initialization file
require_once 'init.php';

// Initialize variables with empty values
$username = $password = $email = "";
$username_err = $password_err = $email_err = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Check input errors before attempting signup
    if (empty($username_err) && empty($password_err) && empty($email_err)) {
        // Prepare a select statement to check if email exists
        $sql = "SELECT id FROM user WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameters
            $param_email = $email;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if email already exists
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    // Insert new user into database
                    $sql = "INSERT INTO user (username, email, password) VALUES (?, ?, ?)";

                    if ($stmt = mysqli_prepare($link, $sql)) {
                        // Bind variables to the prepared statement
                        mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_email, $param_password);

                        // Set parameters
                        $param_username = $username;
                        $param_email = $email;
                        $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($stmt)) {
                            // Redirect user to login page
                            header("location: login.html");
                        } else {
                            echo "Oops! Something went wrong. Please try again later.";
                        }

                        // Close statement
                        mysqli_stmt_close($stmt);
                    }
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close the connection
    mysqli_close($link);
}
?>
