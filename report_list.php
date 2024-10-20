<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';  // Include the database connection

// Fetch logged-in user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch user info from the admin_users table
$result = mysqli_query($conn, "SELECT * FROM admin_users WHERE id = $user_id");
if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);  // Fetch the logged-in user's data
} else {
    echo "User not found.";
    exit();
}

// Fetch all reports (for preview) from the database
$query = "SELECT r.*, u.name as user_name, d.name as docket_name, s.year as spiritual_year
          FROM reports r
          JOIN admin_users u ON r.user_id = u.id
          JOIN dockets d ON r.docket_id = d.id
          JOIN spiritual_years s ON r.spiritual_year_id = s.id
          ORDER BY r.created_at DESC";

$reports_result = mysqli_query($conn, $query);

// Debugging: Check if the query executed successfully
if (!$reports_result) {
    echo "Error fetching reports: " . mysqli_error($conn);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports List</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Reports List</h1>
    
    <p>Hello, <?php echo htmlspecialchars($user['name']); ?>! Here are the available reports:</p>
    
    <?php if (mysqli_num_rows($reports_result) > 0) { ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Docket</th>
                    <th>Spiritual Year</th>
                    <th>Status</th>
                    <th>Compiled By</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                while ($report = mysqli_fetch_assoc($reports_result)) {
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($report['docket_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($report['spiritual_year'] ?? 'N/A'); ?></td>
                        <td><?php echo $report['status'] === 'completed' ? 'Completed' : 'Draft'; ?></td>
                        <td><?php echo htmlspecialchars($report['user_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                        <td>
                            <?php if ($report['user_id'] == $user_id) { ?>
                                <?php if ($report['status'] === 'completed') { ?>
                                    <a href="download_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-success">Download PDF</a>
                                <?php } else { ?>
                                    <a href="preview_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-warning">Preview</a>
                                    <a href="edit_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-primary">Edit</a>
                                <?php } ?>
                            <?php } else { ?>
                                <a href="preview_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-info">Preview</a>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No reports available.</p>
    <?php } ?>
</div>
</body>
</html>
