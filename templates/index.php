<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Display</title>
    <link rel="stylesheet" type="text/css" href="{{ url_for('static', filename='resume.css') }}">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ url_for('static', filename='styles.css') }}">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" style="background-color: #393e46c7;">
        <a class="navbar-brand mr-5" href="http://127.0.0.1:5000/">
            <img src="{{ url_for('static', filename='grey_logo.png') }}" alt="Logo" style="max-height: 50px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="http://127.0.0.1:5000/">New Project</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-3" href="{{ url_for('static', filename='grey_logo.png') }}">Projects</a>
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
        </div>
    </nav>
    <br><br>
    <div class="chat-container" style="display: center">
        <div class="chat-header">
            <div class="col-md-12">
                <h1 class="mb-4">New Project</h1>
                <form method="POST" action="/">
                    <div class="form-group">
                        <textarea class="form-control" id="job_description" name="job_description" placeholder="Type the description of your project here ..." rows="2" required></textarea>
                    </div>
                    <button type="submit" name="action" value="Percentage Match" class="chat-btn">Find Best Match</button>
                </form>
        
                <div class="mt-5">
                    {% if results %}
                        <h2>Results:</h2>
                        <ul class="list-group">
                            {% for result in results %}
                                <li class="list-group-item">
                                    <h3>{{ result.role }}</h3>
                                    <ul class="list-group">
                                        {% for resume in result.resumes %}
                                            <li class="list-group-item">
                                                {{ resume.name }}
                                                <a href="{{ resume.view_pdf_url }}" class="btn btn-primary ml-3">View PDF</a>
                                            </li>
                                        {% endfor %}
                                    </ul>
                                </li>
                            {% endfor %}
                        </ul>
                        <form method="GET" action="/">
                            <button type="submit" class="chat-btn" name="action" value="Validate Team">Validate Team</button>
                        </form>
                    {% else %}
                        <p>No matching resumes found.</p>
                    {% endif %}
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
