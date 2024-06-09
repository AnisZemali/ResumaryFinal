<?php
session_start();

// Check if the user is logged in and is an employee
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "employee") {
    header("location: login.php");
    exit();
}

// Include the database connection
require_once 'init.php';

// Get the employee's resume ID from the session
$r_id = $_SESSION['r_id'];

// Prepare the SQL statement to fetch the team data
$sql = "SELECT t.p_id, t.evaluation, t.role, p.title, p.description 
        FROM teams t
        JOIN project p ON t.p_id = p.id
        WHERE t.r_id = ?";
$stmt = $link->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $r_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("SQL preparation failed: " . $link->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Assigned Team</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>
    <h1>Your Assigned Team</h1>
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Project ID</th>
                    <th>Project Title</th>
                    <th>Project Description</th>
                    <th>Role</th>
                    <th>Evaluation</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["p_id"]); ?></td>
                        <td><?php echo htmlspecialchars($row["title"]); ?></td>
                        <td><?php echo htmlspecialchars($row["description"]); ?></td>
                        <td><?php echo htmlspecialchars($row["role"]); ?></td>
                        <td><?php echo htmlspecialchars($row["evaluation"]); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No teams assigned to you at the moment.</p>
    <?php endif; ?>
    <?php $stmt->close(); ?>
    <?php $link->close(); ?>
</body>
</html>
