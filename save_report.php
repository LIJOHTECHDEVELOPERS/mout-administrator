<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $docket_id = mysqli_real_escape_string($conn, $_POST['docket_id']);
    $spiritual_year_id = mysqli_real_escape_string($conn, $_POST['spiritual_year_id']);
    $greetings = mysqli_real_escape_string($conn, $_POST['greetings']);
    $responsibilities = mysqli_real_escape_string($conn, $_POST['responsibilities']);
    $accomplishments = mysqli_real_escape_string($conn, $_POST['accomplishments']);
    $challenges = mysqli_real_escape_string($conn, $_POST['challenges']);
    $recognitions = mysqli_real_escape_string($conn, $_POST['recognitions']);
    $recommendations = mysqli_real_escape_string($conn, $_POST['recommendations']);
    $conclusion = mysqli_real_escape_string($conn, $_POST['conclusion']);

    // Insert the report into the database
    $sql = "INSERT INTO reports (user_id, docket_id, spiritual_year_id, greetings, responsibilities, accomplishments, challenges, recognitions, recommendations, conclusion, status) 
            VALUES ('$user_id', '$docket_id', '$spiritual_year_id', '$greetings', '$responsibilities', '$accomplishments', '$challenges', '$recognitions', '$recommendations', '$conclusion', 'draft')";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: report_list.php');
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
