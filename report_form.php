<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect to the login page
    header('Location: login.php');
    exit();
}

include 'db.php';  // Include the database connection

// Fetch logged-in user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch user info from the database
$result = mysqli_query($conn, "SELECT * FROM admin_users WHERE id = $user_id");

// Check if the user exists in the database
if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);  // Fetch user data
} else {
    // Handle case where user is not found
    echo "User not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generator</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .step { display: none; }
        .active { display: block; }
    </style>
</head>
<body>

<div class="container">
    <!-- Greet the user by name -->
    <h1>Hello, <?php echo htmlspecialchars($user['name']); ?>!</h1>

    <form id="reportForm" method="post" action="save_report.php">
        <!-- Pass the logged-in user's ID -->
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

        <!-- Step 1: Docket -->
        <div class="step active" id="step1">
            <h3>Select Docket</h3>
            <select name="docket_id" class="form-control" required>
                <option value="">-- Select Docket --</option>
                <?php
                $dockets = mysqli_query($conn, "SELECT * FROM dockets");
                while ($docket = mysqli_fetch_assoc($dockets)) {
                    echo "<option value='{$docket['id']}'>{$docket['name']}</option>";
                }
                ?>
            </select>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(2)">Next</button>
        </div>

        <!-- Step 2: Spiritual Year -->
        <div class="step" id="step2">
            <h3>Select Spiritual Year</h3>
            <select name="spiritual_year_id" class="form-control" required>
                <option value="">-- Select Spiritual Year --</option>
                <?php
                $years = mysqli_query($conn, "SELECT * FROM spiritual_years");
                while ($year = mysqli_fetch_assoc($years)) {
                    echo "<option value='{$year['id']}'>{$year['year']}</option>";
                }
                ?>
            </select>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(1)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(3)">Next</button>
        </div>

        <!-- Step 3: Greetings -->
        <div class="step" id="step3">
            <h3>Greetings</h3>
            <textarea name="greetings" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(2)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(4)">Next</button>
        </div>

        <!-- Step 4: Responsibilities -->
        <div class="step" id="step4">
            <h3>Responsibilities</h3>
            <textarea name="responsibilities" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(3)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(5)">Next</button>
        </div>

        <!-- Step 5: Accomplishments -->
        <div class="step" id="step5">
            <h3>Accomplishments</h3>
            <textarea name="accomplishments" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(4)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(6)">Next</button>
        </div>

        <!-- Step 6: Challenges -->
        <div class="step" id="step6">
            <h3>Challenges</h3>
            <textarea name="challenges" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(5)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(7)">Next</button>
        </div>

        <!-- Step 7: Recognitions -->
        <div class="step" id="step7">
            <h3>Recognitions</h3>
            <textarea name="recognitions" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(6)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(8)">Next</button>
        </div>

        <!-- Step 8: Recommendations -->
        <div class="step" id="step8">
            <h3>Recommendations</h3>
            <textarea name="recommendations" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(7)">Back</button>
            <button type="button" class="btn btn-primary mt-3" onclick="nextStep(9)">Next</button>
        </div>

        <!-- Step 9: Conclusion -->
        <div class="step" id="step9">
            <h3>Conclusion</h3>
            <textarea name="conclusion" class="form-control" rows="3" required></textarea>
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(8)">Back</button>
            <button type="submit" name="action" value="complete" class="btn btn-success mt-3">Complete Report</button>
            <button type="submit" name="action" value="save_draft" class="btn btn-warning mt-3">Save as Draft</button>
        </div>
    </form>
</div>

<script>
    function nextStep(step) {
        document.querySelector('.step.active').classList.remove('active');
        document.getElementById('step' + step).classList.add('active');
    }

    function prevStep(step) {
        document.querySelector('.step.active').classList.remove('active');
        document.getElementById('step' + step).classList.add('active');
    }
</script>

</body>
</html>
