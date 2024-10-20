<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';  // Include the database connection

// Fetch the logged-in user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch the report ID from the query parameter
if (!isset($_GET['report_id'])) {
    echo "No report ID provided.";
    exit();
}

$report_id = (int) $_GET['report_id'];

// Fetch report details
$query = "SELECT * FROM reports WHERE id = $report_id AND user_id = $user_id AND status = 'draft'";
$report_result = mysqli_query($conn, $query);

if (!$report_result || mysqli_num_rows($report_result) == 0) {
    echo "Report not found or you do not have permission to edit this report.";
    exit();
}

$report = mysqli_fetch_assoc($report_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated data from the form
    $greetings = mysqli_real_escape_string($conn, $_POST['greetings']);
    $responsibilities = mysqli_real_escape_string($conn, $_POST['responsibilities']);
    $accomplishments = mysqli_real_escape_string($conn, $_POST['accomplishments']);
    $challenges = mysqli_real_escape_string($conn, $_POST['challenges']);
    $recognitions = mysqli_real_escape_string($conn, $_POST['recognitions']);
    $recommendations = mysqli_real_escape_string($conn, $_POST['recommendations']);
    $conclusion = mysqli_real_escape_string($conn, $_POST['conclusion']);
    $status = isset($_POST['complete']) ? 'completed' : 'draft';

    // Update the report in the database
    $update_query = "UPDATE reports SET 
        greetings = '$greetings',
        responsibilities = '$responsibilities',
        accomplishments = '$accomplishments',
        challenges = '$challenges',
        recognitions = '$recognitions',
        recommendations = '$recommendations',
        conclusion = '$conclusion',
        status = '$status',
        updated_at = NOW()
        WHERE id = $report_id AND user_id = $user_id";

    if (mysqli_query($conn, $update_query)) {
        // Redirect to the reports list after successful update
        header('Location: report_list.php');
        exit();
    } else {
        echo "Error updating report: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Edit Report</h1>
    
    <form action="edit_report.php?report_id=<?php echo $report_id; ?>" method="POST">
        <div class="form-group">
            <label for="greetings">Greetings</label>
            <textarea id="greetings" name="greetings" class="form-control" required><?php echo htmlspecialchars($report['greetings']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="responsibilities">Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" class="form-control" required><?php echo htmlspecialchars($report['responsibilities']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="accomplishments">Accomplishments</label>
            <textarea id="accomplishments" name="accomplishments" class="form-control" required><?php echo htmlspecialchars($report['accomplishments']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="challenges">Challenges</label>
            <textarea id="challenges" name="challenges" class="form-control" required><?php echo htmlspecialchars($report['challenges']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="recognitions">Recognitions</label>
            <textarea id="recognitions" name="recognitions" class="form-control" required><?php echo htmlspecialchars($report['recognitions']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="recommendations">Recommendations</label>
            <textarea id="recommendations" name="recommendations" class="form-control" required><?php echo htmlspecialchars($report['recommendations']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="conclusion">Conclusion</label>
            <textarea id="conclusion" name="conclusion" class="form-control" required><?php echo htmlspecialchars($report['conclusion']); ?></textarea>
        </div>

        <div class="form-group">
            <button type="submit" name="save" class="btn btn-primary">Save as Draft</button>
            <button type="submit" name="complete" class="btn btn-success">Complete Report</button>
        </div>
    </form>

    <a href="report_list.php" class="btn btn-secondary">Back to Reports List</a>
</div>
</body>
</html>
