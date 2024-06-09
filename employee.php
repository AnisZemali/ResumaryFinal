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
                      background-color: #FF5722;
                      border-color: #FF5722;
                    }
                    .archive-form .btn-primary:hover {
                      background-color: #FF5722;
                      border-color: #FF5722;
                    }
                    /* Dropdown Menu */
                    .dropdown-menu-right {
                      right: 0;
                      left: auto;
                    }
                    .btn-custom {
                      color: #fff;
                      background-color: #FF5722;
                      border-color: #FF5722;
                    }
                    .btn-custom:hover {
                      background-color: #FF5722;
                      border-color: #F4511E;
                    }
                    <style>
 
</style>

                  </style>
                </head>
                <body>
                  <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container">
                      <a class="navbar-brand" href="#">Resumary</a>
                      <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ml-auto">
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              Profile
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                              <a class="dropdown-item" href="change_password.php">Change Password</a>
                              <a class="dropdown-item" href="employee.php">Edit Profile</a>
                              <div class="dropdown-divider"></div>
                              <a class="dropdown-item" href="logout.php">Logout</a>
                            </div>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </nav>
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
                          <div class="form-group">
                            <input type="submit" class="btn btn-primary" value="Save Changes">
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
                  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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


?>
