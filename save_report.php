<?php
session_start();
include 'db.php';  // Connect to your database

// Fetch the data from the form and escape the values properly
$user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
$docket_id = mysqli_real_escape_string($conn, $_POST['docket_id']);
$spiritual_year_id = mysqli_real_escape_string($conn, $_POST['spiritual_year_id']);
$greetings = isset($_POST['greetings']) ? mysqli_real_escape_string($conn, $_POST['greetings']) : null;
$responsibilities = isset($_POST['responsibilities']) ? mysqli_real_escape_string($conn, $_POST['responsibilities']) : null;
$accomplishments = isset($_POST['accomplishments']) ? mysqli_real_escape_string($conn, $_POST['accomplishments']) : null;
$challenges = isset($_POST['challenges']) ? mysqli_real_escape_string($conn, $_POST['challenges']) : null;
$recognitions = isset($_POST['recognitions']) ? mysqli_real_escape_string($conn, $_POST['recognitions']) : null;
$recommendations = isset($_POST['recommendations']) ? mysqli_real_escape_string($conn, $_POST['recommendations']) : null;
$conclusion = isset($_POST['conclusion']) ? mysqli_real_escape_string($conn, $_POST['conclusion']) : null;

$action = $_POST['action']; // Check if the form was saved as 'complete' or 'draft'
$status = ($action == 'complete') ? 'completed' : 'draft';  // Set status based on action

// Check if a report for the user already exists
$check_query = "SELECT * FROM reports WHERE user_id = '$user_id' AND docket_id = '$docket_id' AND spiritual_year_id = '$spiritual_year_id'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) > 0) {
    // If a report exists, update it
    $query = "
        UPDATE reports SET 
            greetings = '$greetings', 
            responsibilities = '$responsibilities', 
            accomplishments = '$accomplishments', 
            challenges = '$challenges', 
            recognitions = '$recognitions', 
            recommendations = '$recommendations', 
            conclusion = '$conclusion', 
            status = '$status',
            updated_at = NOW()
        WHERE user_id = '$user_id' AND docket_id = '$docket_id' AND spiritual_year_id = '$spiritual_year_id'
    ";
} else {
    // If no report exists, insert a new one
    $query = "
        INSERT INTO reports (
            user_id, docket_id, spiritual_year_id, greetings, responsibilities, accomplishments, challenges, recognitions, recommendations, conclusion, status, created_at, updated_at
        ) 
        VALUES (
            '$user_id', '$docket_id', '$spiritual_year_id', '$greetings', '$responsibilities', '$accomplishments', '$challenges', '$recognitions', '$recommendations', '$conclusion', '$status', NOW(), NOW()
        )
    ";
}

// Execute the query
if (mysqli_query($conn, $query)) {
    header("Location: report_list.php");  // Redirect after successful save
    exit();
} else {
    // Output the error if query fails
    echo "Error: " . mysqli_error($conn);
}
?>
