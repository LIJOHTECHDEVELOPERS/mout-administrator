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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
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
</body>
</html>
