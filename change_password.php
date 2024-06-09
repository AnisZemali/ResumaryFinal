<?php
session_start();

// Check if user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "employee") {
    header("location: login.php");
    exit;
}

// Include the initialization file
require_once 'init.php';

$errorMessage = '';

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the password change form is submitted
    if (!empty($_POST['oldPassword']) && !empty($_POST['newPassword']) && !empty($_POST['confirmPassword'])) {
        $oldPassword = $_POST['oldPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];
        $userId = $_SESSION["id"];

        // Validate old password
        $sql = "SELECT password FROM resume2 WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($oldPassword, $hashed_password)) {
                            // Check password strength
                            if (strlen($newPassword) < 8) {
                                $errorMessage = "Password must be at least 8 characters long.";
                            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
                                $errorMessage = "Password must contain at least one lowercase letter, one uppercase letter, and one digit.";
                            } elseif (!preg_match('/[ `!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?~]/', $newPassword)) {
                                $errorMessage = "Password must contain at least one special character.";
                            } elseif ($newPassword !== $confirmPassword) {
                                $errorMessage = "New password and confirm password do not match.";
                            } else {
                                // Update password in the database
                                $updatePasswordSql = "UPDATE resume2 SET password = ? WHERE id = ?";
                                if ($updatePasswordStmt = mysqli_prepare($link, $updatePasswordSql)) {
                                    $hashed_new_password = password_hash($newPassword, PASSWORD_DEFAULT);
                                    mysqli_stmt_bind_param($updatePasswordStmt, "si", $hashed_new_password, $userId);
                                    if (mysqli_stmt_execute($updatePasswordStmt)) {
                                        // Password updated successfully
                                        $successMessage = "Password updated successfully!";
                                    } else {
                                        $errorMessage = "Failed to update password. Please try again later.";
                                    }
                                    mysqli_stmt_close($updatePasswordStmt);
                                } else {
                                    $errorMessage = "Failed to prepare statement for updating password.";
                                }
                            }
                        } else {
                            $errorMessage = "Invalid old password.";
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Close connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f3f3f3;
        }
        .container {
            margin-top: 50px;
        }
        .change-password-container {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .change-password-header {
            background-color: #f0f0f0;
            border-bottom: 1px solid #ccc;
            padding: 10px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .change-password-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .change-password-form {
            margin-top: 20px;
        }
        .change-password-form label {
            font-weight: bold;
        }
        .change-password-form .btn-primary {
            background-color: #FF5722;
            border-color: #FF5722
            ;
        }
        .change-password-form .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="change-password-container">
            <div class="change-password-header">
                <h1>Change Password</h1>
            </div>
            <div class="change-password-form">
                <form id="changePasswordForm" method="post">
                    <div class="form-group">
                        <label for="oldPassword">Old Password:</label>
                        <input type="password" class="form-control" id="oldPassword" name="oldPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password:</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password:</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Change Password">
                    </div>
                </form>
                <?php
                if (!empty($errorMessage)) {
                    echo '<script>alert("' . $errorMessage . '");</script>';
                }
                if (!empty($successMessage)) {
                    echo '<script>alert("' . $successMessage . '");</script>';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
