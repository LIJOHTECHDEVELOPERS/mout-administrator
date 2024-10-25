<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$query = "SELECT * FROM reports WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $report_id, $user_id);
mysqli_stmt_execute($stmt);
$report_result = mysqli_stmt_get_result($stmt);

if (!$report_result || mysqli_num_rows($report_result) == 0) {
    echo "Report not found or you do not have permission to edit this report.";
    exit();
}

$report = mysqli_fetch_assoc($report_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated data from the form
    $greetings = $_POST['greetings'];
    $responsibilities = $_POST['responsibilities'];
    $accomplishments = $_POST['accomplishments'];
    $challenges = $_POST['challenges'];
    $recognitions = $_POST['recognitions'];
    $recommendations = $_POST['recommendations'];
    $conclusion = $_POST['conclusion'];
    
    // Determine the status based on which button was clicked
    $status = isset($_POST['complete']) ? 'completed' : 'draft';

    // Prepare the update query
    $update_query = "UPDATE reports SET 
        greetings = ?,
        responsibilities = ?,
        accomplishments = ?,
        challenges = ?,
        recognitions = ?,
        recommendations = ?,
        conclusion = ?,
        status = ?
        WHERE id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssssssssii", 
        $greetings, $responsibilities, $accomplishments, $challenges, 
        $recognitions, $recommendations, $conclusion, $status, 
        $report_id, $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        // Redirect to the reports list after successful update
        header('Location: report_list.php');
        exit();
    } else {
        $error_message = "Error updating report: " . mysqli_error($conn);
    }
}
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
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .step { display: none; }
        .step.active { display: block; }
        .ql-editor { min-height: 200px; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <?php include 'header.php'; ?>
        <div class="container">
            <div class="page-inner">
                <h1 class="mb-4">Edit Report</h1>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: 14.28%;" aria-valuenow="14.28" aria-valuemin="0" aria-valuemax="100">1/7</div>
                </div>
                <form id="reportForm" method="POST">
                    <div class="step active" data-step="1">
                        <h3>Greetings</h3>
                        <div id="greetings-editor"><?php echo $report['greetings']; ?></div>
                        <input type="hidden" name="greetings" id="greetings-input">
                    </div>
                    <div class="step" data-step="2">
                        <h3>Responsibilities</h3>
                        <div id="responsibilities-editor"><?php echo $report['responsibilities']; ?></div>
                        <input type="hidden" name="responsibilities" id="responsibilities-input">
                    </div>
                    <div class="step" data-step="3">
                        <h3>Accomplishments</h3>
                        <div id="accomplishments-editor"><?php echo $report['accomplishments']; ?></div>
                        <input type="hidden" name="accomplishments" id="accomplishments-input">
                    </div>
                    <div class="step" data-step="4">
                        <h3>Challenges</h3>
                        <div id="challenges-editor"><?php echo $report['challenges']; ?></div>
                        <input type="hidden" name="challenges" id="challenges-input">
                    </div>
                    <div class="step" data-step="5">
                        <h3>Recognitions</h3>
                        <div id="recognitions-editor"><?php echo $report['recognitions']; ?></div>
                        <input type="hidden" name="recognitions" id="recognitions-input">
                    </div>
                    <div class="step" data-step="6">
                        <h3>Recommendations</h3>
                        <div id="recommendations-editor"><?php echo $report['recommendations']; ?></div>
                        <input type="hidden" name="recommendations" id="recommendations-input">
                    </div>
                    <div class="step" data-step="7">
                        <h3>Conclusion</h3>
                        <div id="conclusion-editor"><?php echo $report['conclusion']; ?></div>
                        <input type="hidden" name="conclusion" id="conclusion-input">
                    </div>
                    <div class="mt-4">
                        <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;">Previous</button>
                        <button type="button" id="nextBtn" class="btn btn-primary">Next</button>
                        <button type="submit" id="saveBtn" name="save" class="btn btn-success" style="display: none;">Save as Draft</button>
                        <button type="submit" id="completeBtn" name="complete" class="btn btn-info" style="display: none;">Complete Report</button>
                    </div>
                </form>
                <a href="report_list.php" class="btn btn-secondary mt-3">Back to Reports List</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    const editors = {};
    const editorIds = ['greetings', 'responsibilities', 'accomplishments', 'challenges', 'recognitions', 'recommendations', 'conclusion'];

    editorIds.forEach(id => {
        editors[id] = new Quill(`#${id}-editor`, {
            theme: 'snow',
            placeholder: `Enter ${id} here...`,
        });
    });

    let currentStep = 1;
    const totalSteps = 7;

    function updateStep(step) {
        document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
        document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
        document.querySelector('.progress-bar').style.width = `${(step / totalSteps) * 100}%`;
        document.querySelector('.progress-bar').textContent = `${step}/${totalSteps}`;

        document.getElementById('prevBtn').style.display = step > 1 ? 'inline-block' : 'none';
        document.getElementById('nextBtn').style.display = step < totalSteps ? 'inline-block' : 'none';
        document.getElementById('saveBtn').style.display = step === totalSteps ? 'inline-block' : 'none';
        document.getElementById('completeBtn').style.display = step === totalSteps ? 'inline-block' : 'none';
    }

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentStep < totalSteps) {
            currentStep++;
            updateStep(currentStep);
        }
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStep(currentStep);
        }
    });

    document.getElementById('reportForm').addEventListener('submit', (e) => {
        editorIds.forEach(id => {
            document.getElementById(`${id}-input`).value = editors[id].root.innerHTML;
        });
    });

    updateStep(currentStep);
</script>
</body>
</html>
