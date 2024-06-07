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
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" style="background-color: #393e46c7;">
        <a class="navbar-brand mr-5" href="#">
            <img src="img/grey_logo.png" alt="Logo" style="max-height: 50px">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="http://127.0.0.1:5000">New Project</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="">Projects</a>
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
<div class="card-grid">
    <?php
// Assuming you have a database connection established
include 'init.php';
// Retrieve project information
$projectQuery = "SELECT * FROM project";
$projectResult = mysqli_query($link, $projectQuery);

while ($projectRow = mysqli_fetch_assoc($projectResult)) {
    $projectId = $projectRow['id'];
    $projectName = $projectRow['title'];
    $projectDesc = $projectRow['description'];
    
    // Retrieve team members for the project
    $teamQuery = "SELECT r.firstname FROM resume r
                  INNER JOIN teams t ON r.id = t.r_id
                  WHERE t.p_id = $projectId";
    $teamResult = mysqli_query($link, $teamQuery);
// Assuming you have retrieved the project data from the database and stored it in an array called $projects

  echo '<div class="card">';
  echo '<table>';
  echo '<tr>';
  echo '<th>' . $projectName. '</th>';
  echo '</tr>';
  while ($teamRow = mysqli_fetch_assoc($teamResult)){
    $workerFirstName = $teamRow['firstname'];
  echo '<tr>'; 
  echo '<td>' . $workerFirstName . '</td>';
  echo '</tr>';
  }
  echo '</div>';
  echo '</table>';
  echo '<form action="Viewmore.php" method="GET">';
  echo '<input type="hidden" name="id" value="';
  echo $projectId;
  echo '">';
  echo '<button class="view-more" type="submit">View More</button>';
  echo '</form>';
  echo '</div>';

}
?>
</body>
</html>


