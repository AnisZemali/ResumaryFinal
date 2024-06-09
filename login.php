<?php
// Include the initialization file
require_once 'init.php';
session_start();// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Get the selected role from the form and validate it
    $role = $_POST["role"];
    if ($role !== "admin" && $role !== "employee") {
        echo "Invalid role selected.";
        exit();
    }

    // Check input errors before querying the database
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement based on the selected role
        if ($role === "admin") {
            $sql = "SELECT id, username, email, password FROM user WHERE email = ?";
        } elseif ($role === "employee") {
            $sql = "SELECT id, name, email, password FROM resume2 WHERE email = ?";
        }

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameters
            $param_email = $email;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if email exists, if yes then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $email, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role; // Store the role in session
                            $_SESSION["role"] = $role;
                            $_SESSION["r_id"] = $r_id; 


                            // Redirect user to appropriate dashboard page
                            if ($role === "admin") {
                                header("location: projects.php");
                            } elseif ($role === "employee") {
                                header("location: employee.php");
                            }
                            exit();
                        } else {
                            // Password is not valid
                            echo '<script>alert("Incorrect password. Please try again.");</script>';
                        }
                    }
                } else {
                    // Email doesn't exist
                    echo '<script>alert("No account found with that email.");</script>';
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>
