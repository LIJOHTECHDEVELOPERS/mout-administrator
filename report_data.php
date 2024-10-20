<?php
// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "leadersportal";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check which form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["form_type"] == "docket") {
        $docket_name = sanitize_input($_POST["docket_name"]);
        
        $sql = "INSERT INTO dockets (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $docket_name);
        
        if ($stmt->execute()) {
            echo "New docket added successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        
        $stmt->close();
    } elseif ($_POST["form_type"] == "spiritual_year") {
        $spiritual_year = sanitize_input($_POST["spiritual_year"]);
        
        $sql = "INSERT INTO spiritual_years (year) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $spiritual_year);
        
        if ($stmt->execute()) {
            echo "New spiritual year added successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport"/>
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon"/>
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {families: ["Public Sans:300,400,500,600,700"]},
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["assets/css/fonts.min.css"],
            },
            active: function () {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/css/plugins.min.css"/>
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">
      <!-- Include the sidebar -->
      <?php include 'sidebar.php'; ?>

      <!-- Main Panel -->
      <div class="main-panel">
      <?php include 'header.php'; ?>
      <div class="container">
        <div class="page-inner">
    <div class="container mt-5">
        <h1 class="mb-4">Database Input Form</h1>
        
        <form id="docketForm" class="mb-5" action="report_data.php" method="post">
            <h2>Add Docket</h2>
            <div class="mb-3">
                <label for="docketName" class="form-label">Docket Name</label>
                <input type="text" class="form-control" id="docketName" name="docket_name" required>
            </div>
            <input type="hidden" name="form_type" value="docket">
            <button type="submit" class="btn btn-primary">Submit Docket</button>
        </form>

        <form id="spiritualYearForm" action="report_data.php" method="post">
            <h2>Add Spiritual Year</h2>
            <div class="mb-3">
                <label for="spiritualYear" class="form-label">Spiritual Year</label>
                <input type="text" class="form-control" id="spiritualYear" name="spiritual_year" required pattern="\d{4}-\d{4}" placeholder="YYYY-YYYY">
                <div class="form-text">Please enter the year in the format YYYY-YYYY (e.g., 2023-2024)</div>
            </div>
            <input type="hidden" name="form_type" value="spiritual_year">
            <button type="submit" class="btn btn-primary">Submit Spiritual Year</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>