<?php
session_start();

// Check if user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "employee") {
    header("location: login.php");
    exit;
}

// Include the initialization file
require_once 'init.php';

// Fetch the current employee's information
$sql = "SELECT name, email, phoneNumber, skills, experience, education FROM resume2 WHERE email = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $param_email);

    // Set parameters
    $param_email = $_SESSION["email"];

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // Store result
        mysqli_stmt_store_result($stmt);

        // Check if email exists, if yes then fetch the details
        if (mysqli_stmt_num_rows($stmt) == 1) {
            // Bind result variables
            mysqli_stmt_bind_result($stmt, $name, $email, $phoneNumber, $skills, $experience, $education);
            if (mysqli_stmt_fetch($stmt)) {
                // Employee details fetched successfully

                // Process form data when form is submitted
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    // Define variables to store form data
                    $newName = $_POST["name"];
                    $newEmail = $_POST["email"];
                    $newPhoneNumber = $_POST["phoneNumber"];
                    $newSkills = $_POST["skills"];
                    $newExperience = $_POST["experience"];
                    $newEducation = $_POST["education"];

                    // Check if the password change form is submitted
                    if (!empty($_POST['oldPassword']) && !empty($_POST['newPassword']) && !empty($_POST['confirmPassword'])) {
                        $oldPassword = $_POST['oldPassword'];
                        $newPassword = $_POST['newPassword'];
                        $confirmPassword = $_POST['confirmPassword'];

                        // Validate old password
                        $sql = "SELECT password FROM resume2 WHERE email = ?";
                        if ($stmt = mysqli_prepare($link, $sql)) {
                            mysqli_stmt_bind_param($stmt, "s", $param_email);
                            $param_email = $_SESSION["email"];
                            if (mysqli_stmt_execute($stmt)) {
                                mysqli_stmt_store_result($stmt);
                                if (mysqli_stmt_num_rows($stmt) == 1) {
                                    mysqli_stmt_bind_result($stmt, $hashed_password);
                                    if (mysqli_stmt_fetch($stmt)) {
                                        if (password_verify($oldPassword, $hashed_password)) {
                                            // Check email format using regular expression
                                            $emailPattern = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
                                            if (!preg_match($emailPattern, $newEmail)) {
                                                echo "Please enter a valid email address.";
                                                exit;
                                            }

                                            // Check password strength
                                            if (strlen($newPassword) < 8) {
                                                echo "Password must be at least 8 characters long.";
                                                exit;
                                            }

                                            // Check for at least one lowercase letter, one uppercase letter, and one digit
                                            $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';
                                            if (!preg_match($passwordPattern, $newPassword)) {
                                                echo "Password must contain at least one lowercase letter, one uppercase letter, and one digit.";
                                                exit;
                                            }

                                            // Check for special characters
                                            $specialCharacterPattern = '/[ `!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?~]/';
                                            if (!preg_match($specialCharacterPattern, $newPassword)) {
                                                echo "Password must contain at least one special character.";
                                                exit;
                                            }

                                            // Validate new password
                                            if ($newPassword === $confirmPassword) {
                                                // Update password in the database
                                                $updatePasswordSql = "UPDATE resume2 SET password = ? WHERE email = ?";
                                                if ($updatePasswordStmt = mysqli_prepare($link, $updatePasswordSql)) {
                                                    $hashed_new_password = password_hash($newPassword, PASSWORD_DEFAULT);
                                                    mysqli_stmt_bind_param($updatePasswordStmt, "ss", $hashed_new_password, $param_email);
                                                    if (mysqli_stmt_execute($updatePasswordStmt)) {
                                                        // Password updated successfully
                                                        echo "Password updated successfully!";
                                                    } else {
                                                        echo "Failed to update password. Please try again later.";
                                                    }
                                                    mysqli_stmt_close($updatePasswordStmt);
                                                } else {
                                                    echo "Failed to prepare statement for updating password.";
                                                }
                                            } else {
                                                echo "New password and confirm password do not match.";
                                            }
                                        } else {
                                            echo "Invalid old password.";
                                        }
                                    }
                                }
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }

                    // Prepare an update statement for other user details
                    $updateSql = "UPDATE resume2 SET name=?, email=?, phoneNumber=?, skills=?, experience=?, education=? WHERE email=?";

                    if ($updateStmt = mysqli_prepare($link, $updateSql)) {
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($updateStmt, "sssssss", $newName, $newEmail, $newPhoneNumber, $newSkills, $newExperience, $newEducation, $_SESSION["email"]);

                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($updateStmt)) {
                            // Retrieve updated information
                            $name = $newName;
                            $email = $newEmail;
                            $phoneNumber = $newPhoneNumber;
                            $skills = $newSkills;
                            $experience = $newExperience;
                            $education = $newEducation;
                        } else {
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                    } else {
                        echo "Failed to prepare the statement.";
                    }

                    // Close statement
                    mysqli_stmt_close($updateStmt);

                    // Close connection
                    mysqli_close($link);
                }
                // Display the form with pre-filled values
                ?>

                <!DOCTYPE html>
                <html lang="en">
                <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>Employee Profile</title>
                  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                  <style>
                    body {
                      background-color: #f3f3f3;
                    }
                    .container {
                      margin-top: 50px;
                    }
                    .archive-container {
                      background-color: #fff;
                      border: 1px solid #ccc;
                      border-radius: 5px;
                      padding: 20px;
                      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }
                    .archive-header {
                      background-color: #f0f0f0;
                      border-bottom: 1px solid #ccc;
                      padding: 10px;
                      border-top-left-radius: 5px;
                      border-top-right-radius: 5px;
                    }
                    .archive-header h1 {
                      margin: 0;
                      font-size: 24px;
                      color: #333;
                    }
                    .archive-form {
                      margin-top: 20px;
                    }
                    .archive-form label {
                      font-weight: bold;
                    }
                    .archive-form textarea {
                      min-height: 150px;
                    }
                    .archive-form .btn-primary {
                      background-color: #007bff;
                      border-color: #007bff;
                    }
                    .archive-form .btn-primary:hover {
                      background-color: #0056b3;
                      border-color: #0056b3;
                    }
                    /* Logout Link */
                    .logout-link {
                      position: absolute;
                      top: 10px;
                      right: 10px;
                    }
                    .btn-custom {
                      color: #fff;
                      background-color: #FF5722;
                      border-color: #FF5722;
                    }
                    .btn-custom:hover {
                      background-color: #F4511E;
                      border-color: #F4511E;
                    }
                  </style>
                </head>
                <body>
                  <div class="logout-link">
                    <a href="logout.php" class="btn btn-custom">Logout</a>
                  </div>
                  <div class="container">
                    <div class="archive-container">
                      <div class="archive-header">
                        <h1>Welcome <?php echo htmlspecialchars($name); ?></h1>
                      </div>
                      <div class="archive-form">
                        <form id="updateForm" method="post">
                          <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
                          </div>
                          <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                          </div>
                          <div class="form-group">
                            <label for="phoneNumber">Phone Number:</label>
                            <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($phoneNumber); ?>">
                          </div>
                          <div class="form-group">
                            <label for="skills">Skills:</label>
                            <textarea class="form-control" id="skills" name="skills"><?php echo htmlspecialchars($skills); ?></textarea>
                          </div>
                          <div class="form-group">
                            <label for="experience">Experience:</label>
                            <textarea class="form-control" id="experience" name="experience"><?php echo htmlspecialchars($experience); ?></textarea>
                          </div>
                          <div class="form-group">
                            <label for="education">Education:</label>
                            <textarea class="form-control" id="education" name="education"><?php echo htmlspecialchars($education); ?></textarea>
                          </div>
                          <hr>
                          <h2>Change Password</h2>
                          <div class="form-group">
                            <label for="oldPassword">Old Password:</label>
                            <input type="password" class="form-control" id="oldPassword" name="oldPassword">
                          </div>
                          <div class="form-group">
                            <label for="newPassword">New Password:</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword">
                          </div>
                          <div class="form-group">
                            <label for="confirmPassword">Confirm New Password:</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                          </div>
                          <div class="form-group">
                            <input type="button" id="saveChanges" class="btn btn-primary" value="Save Changes">
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                  <script>
                    $(document).ready(function() {
                        $("#saveChanges").click(function() {
                            $.ajax({
                                type: "POST",
                                url: "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>",
                                data: $("#updateForm").serialize(),
                                success: function(response) {
                                    // Display success message or handle errors if needed
                                    // In this example, we assume that the updated information is already displayed
                                    alert(response);
                                }
                            });
                        });
                    });
                  </script>
                </body>
                </html>



                <?php
            } else {
                echo "Failed to fetch employee details.";
            }
        }
    } else {
        echo "Failed to execute the query.";
    }
    // Close statement
    mysqli_stmt_close($stmt);
} else {
    echo "Failed to prepare the statement.";
}

// Close connection
mysqli_close($link);
?>
