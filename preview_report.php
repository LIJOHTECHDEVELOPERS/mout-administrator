<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';  // Include the database connection

// Fetch the report ID from the query parameter
if (!isset($_GET['report_id'])) {
    echo "No report ID provided.";
    exit();
}

$report_id = (int) $_GET['report_id'];

// Fetch report details
$query = "SELECT r.*, u.name as user_name, d.name as docket_name, s.year as spiritual_year
          FROM reports r
          JOIN admin_users u ON r.user_id = u.id
          JOIN dockets d ON r.docket_id = d.id
          JOIN spiritual_years s ON r.spiritual_year_id = s.id
          WHERE r.id = $report_id";

$report_result = mysqli_query($conn, $query);

if (!$report_result || mysqli_num_rows($report_result) == 0) {
    echo "Report not found.";
    exit();
}

$report = mysqli_fetch_assoc($report_result);
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <?php include 'header.php'; ?>
<div class="container">
<div class="page-inner">
    <h1>Preview Report</h1>
    
    <table class="table table-bordered">
        <tr>
            <th>Docket</th>
            <td><?php echo htmlspecialchars($report['docket_name']); ?></td>
        </tr>
        <tr>
            <th>Spiritual Year</th>
            <td><?php echo htmlspecialchars($report['spiritual_year']); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><?php echo $report['status'] === 'completed' ? 'Completed' : 'Draft'; ?></td>
        </tr>
        <tr>
            <th>Compiled By</th>
            <td><?php echo htmlspecialchars($report['user_name']); ?></td>
        </tr>
        <tr>
            <th>Date Created</th>
            <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
        </tr>
        <tr>
            <th>Greetings</th>
            <td><?php echo htmlspecialchars($report['greetings']); ?></td>
        </tr>
        <tr>
            <th>Responsibilities</th>
            <td><?php echo htmlspecialchars($report['responsibilities']); ?></td>
        </tr>
        <tr>
            <th>Accomplishments</th>
            <td><?php echo htmlspecialchars($report['accomplishments']); ?></td>
        </tr>
        <tr>
            <th>Challenges</th>
            <td><?php echo htmlspecialchars($report['challenges']); ?></td>
        </tr>
        <tr>
            <th>Recognitions</th>
            <td><?php echo htmlspecialchars($report['recognitions']); ?></td>
        </tr>
        <tr>
            <th>Recommendations</th>
            <td><?php echo htmlspecialchars($report['recommendations']); ?></td>
        </tr>
        <tr>
            <th>Conclusion</th>
            <td><?php echo htmlspecialchars($report['conclusion']); ?></td>
        </tr>
    </table>
    
    <a href="report_list.php" class="btn btn-secondary">Back to Reports List</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>
