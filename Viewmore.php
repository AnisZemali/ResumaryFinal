<?php
session_start();
include 'init.php';
// Get the ID from the query parameter
$itemId = $_GET['id'];
$_SESSION["p_id"]=$itemId;
$pquery = "SELECT * FROM project WHERE id = $itemId";
$projectresult = mysqli_query($link, $pquery);
$project =  mysqli_fetch_assoc($projectresult);
$projectId = $project["id"];
$projectDesc = $project["description"];
$projectTitle = $project["title"];   
$projectOwner = $project["u_id"];
$projectState = $project["state"];

$teamQuery = "SELECT r.name AS name, t.role 
              FROM resume r
              INNER JOIN teams t ON r.id = t.r_id
              WHERE t.p_id = $projectId";
$teamResult = mysqli_query($link, $teamQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Display</title>
    <link rel="stylesheet" type="text/css" href="../static/resume.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="static/styles.css">
    <link rel="stylesheet" href="static/style.css">
</head>


<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" style="background-color: #393e46c7;">
        <a class="navbar-brand mr-5" href="#">
            <img src="static/grey_logo.png" alt="Logo" style="max-height: 50px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="#">New Project</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="#">New Job</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="#">Projects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-3 font-weight-bold" href="#">Resumes</a>
                </li>
            </ul>
            <form class="form-inline my-2 my-lg-0">
                <div class="input-group rounded">
                    <input type="search" class="form-control rounded" placeholder="Search" aria-label="Search"
                        aria-describedby="search-addon" />
                    <span class="input-group-text border-0" id="search-addon">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </form>
            <!-- Circle with initial letter -->
            <div class="ml-5">
                <div class="circle" style="background-color: #1e6ab7;">
                    <span class="initial-letter text-white">KM</span>
                </div>
            </div>
        </div>
    </nav>
    

<br><br><br><br><br><br>
<div class="card" > 
    <h1> <?php echo $projectTitle  ?> </h1>
</div>
<br>
<div class ="card">
  <?php echo $projectDesc;?>
</div>
<br>
<div class="card1">
    <?php
        echo '<a2> Team Members and evaluation : </a2>';
        echo "<br>";
        echo "<br>";
        echo "<ol>";
        $counter = 1; // Counter to generate unique IDs for sliders
        while ($teamRow = mysqli_fetch_assoc($teamResult)){       
            $workerFirstName = $teamRow['name'];
            $role = $teamRow['role'];
            echo "<li>"; 
            echo '<div class="team-member">';
            echo '<span class="name">' . $role ." : " .$workerFirstName . '</span>';
            echo '<div class="slidecontainer">';
            echo '<input type="range" min="1" max="10" value="5" class="slider" id="myRange' . $counter . '">';
            echo '<div class="slider-value" id="slider-value' . $counter . '"><span id="sliderValue' . $counter;
            echo 'Value' . $counter . '">5</span></div>';
            echo '</div>'; // close slidecontainer
            echo '</div>'; // close team-member
            echo "</li>"; 
            echo "<br>";
            $counter++; // Increment counter for the next iteration
        }
        echo "</ol>"; 
    ?> 
    <form id="validateForm" action="update_project.php" method="POST">
        <button class="validate" type="submit">Validate</button>
    </form>
</div>
</body>

<script>
// JavaScript for dynamically updating slider values
document.addEventListener("DOMContentLoaded", function() {
    var sliders = document.querySelectorAll('.slider');
    sliders.forEach(function(slider) {
        var sliderValue = document.querySelector('#slider-value' + slider.id.slice(-1));
        slider.addEventListener('input', function() {
            sliderValue.textContent = this.value;
        });
    });
});
</script>
</html>