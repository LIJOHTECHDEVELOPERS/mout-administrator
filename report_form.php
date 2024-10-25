<?php
// report_form.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Check if user has already submitted a report for the current period
function hasSubmittedReport($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM reports WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Check if user has a draft report
function hasDraftReport($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM reports WHERE user_id = ? AND status = 'draft'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Perform initial checks
if (hasSubmittedReport($conn, $user_id)) {
    header('Location: report_already_submitted.php');
    exit();
}

// Get current report
function getCurrentReport($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM reports WHERE user_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get current step
function getCurrentStep($conn, $user_id) {
    $report = getCurrentReport($conn, $user_id);
    if (!$report) return 0;
    
    if (empty($report['docket_id'])) return 0;
    if (empty($report['spiritual_year_id'])) return 1;
    if (empty($report['greetings'])) return 2;
    if (empty($report['responsibilities'])) return 3;
    if (empty($report['accomplishments'])) return 4;
    if (empty($report['challenges'])) return 5;
    if (empty($report['recognitions'])) return 6;
    if (empty($report['recommendations'])) return 7;
    if (empty($report['conclusion'])) return 8;
    return 9;
}

$current_report = getCurrentReport($conn, $user_id);
$current_step = getCurrentStep($conn, $user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$current_report && !hasDraftReport($conn, $user_id)) {
            // Create new report only if user doesn't have a draft
            $stmt = $conn->prepare("INSERT INTO reports (user_id, status) VALUES (?, 'draft')");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $current_report = getCurrentReport($conn, $user_id);
        } elseif (!$current_report && hasDraftReport($conn, $user_id)) {
            header('Location: report_form.php');
            exit();
        }

        // Update the current step's field
        $field = '';
        $value = '';
        
        switch ($current_step) {
            case 0:
                $field = 'docket_id';
                $value = $_POST['docket_id'];
                break;
            case 1:
                $field = 'spiritual_year_id';
                $value = $_POST['spiritual_year_id'];
                break;
            case 2:
                $field = 'greetings';
                $value = $_POST['greetings'];
                break;
            case 3:
                $field = 'responsibilities';
                $value = $_POST['responsibilities'];
                break;
            case 4:
                $field = 'accomplishments';
                $value = $_POST['accomplishments'];
                break;
            case 5:
                $field = 'challenges';
                $value = $_POST['challenges'];
                break;
            case 6:
                $field = 'recognitions';
                $value = $_POST['recognitions'];
                break;
            case 7:
                $field = 'recommendations';
                $value = $_POST['recommendations'];
                break;
            case 8:
                $field = 'conclusion';
                $value = $_POST['conclusion'];
                break;
        }

        if ($field && $value) {
            $sql = "UPDATE reports SET $field = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $value, $current_report['id']);
            
            if ($stmt->execute()) {
                if (isset($_POST['complete']) && $_POST['complete'] === 'true') {
                    // Additional check before completing the report
                    if (hasSubmittedReport($conn, $user_id)) {
                        header('Location: report_already_submitted.php');
                        exit();
                    }
                    
                    $stmt = $conn->prepare("UPDATE reports SET status = 'completed' WHERE id = ?");
                    $stmt->bind_param("i", $current_report['id']);
                    $stmt->execute();
                    header('Location: report_completed.php');
                    exit();
                }
                
                header('Location: report_form.php');
                exit();
            } else {
                $error_message = "Failed to save data. Please try again.";
            }
        }
    } catch (Exception $e) {
        $error_message = "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/css/plugins.min.css"/>
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <?php include 'header.php'; ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Create Report - Step <?php echo $current_step + 1; ?> of 9</h2>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Progress Bar -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?php echo ($current_step / 9 * 100); ?>%" 
                         aria-valuenow="<?php echo $current_step; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="9">
                        Step <?php echo $current_step + 1; ?> of 9
                    </div>
                </div>

                <form method="post" action="" id="reportForm">
                    <?php if ($current_step === 0): ?>
                        <div class="form-group mb-3">
                            <label for="docket_id">Select Docket</label>
                            <select name="docket_id" id="docket_id" class="form-control" required>
                                <option value="">-- Select Docket --</option>
                                <?php
                                $dockets = $conn->query("SELECT * FROM dockets");
                                while ($docket = $dockets->fetch_assoc()) {
                                    $selected = ($current_report && $current_report['docket_id'] == $docket['id']) ? 'selected' : '';
                                    echo "<option value='{$docket['id']}' {$selected}>{$docket['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    
                    <?php elseif ($current_step === 1): ?>
                        <div class="form-group mb-3">
                            <label for="spiritual_year_id">Select Spiritual Year</label>
                            <select name="spiritual_year_id" id="spiritual_year_id" class="form-control" required>
                                <option value="">-- Select Spiritual Year --</option>
                                <?php
                                $years = $conn->query("SELECT * FROM spiritual_years");
                                while ($year = $years->fetch_assoc()) {
                                    $selected = ($current_report && $current_report['spiritual_year_id'] == $year['id']) ? 'selected' : '';
                                    echo "<option value='{$year['id']}' {$selected}>{$year['year']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    
                    <?php elseif ($current_step >= 2 && $current_step <= 8): ?>
                        <?php
                        // Define fields with their labels and step numbers
                        $form_fields = [
                            2 => ['field' => 'greetings', 'label' => 'Greetings'],
                            3 => ['field' => 'responsibilities', 'label' => 'Responsibilities'],
                            4 => ['field' => 'accomplishments', 'label' => 'Accomplishments'],
                            5 => ['field' => 'challenges', 'label' => 'Challenges'],
                            6 => ['field' => 'recognitions', 'label' => 'Recognitions'],
                            7 => ['field' => 'recommendations', 'label' => 'Recommendations'],
                            8 => ['field' => 'conclusion', 'label' => 'Conclusion']
                        ];

                        $current_field = $form_fields[$current_step]['field'];
                        $current_label = $form_fields[$current_step]['label'];
                        ?>
                        <div class="form-group mb-3">
                            <label for="<?php echo $current_field; ?>"><?php echo $current_label; ?></label>
                            <textarea name="<?php echo $current_field; ?>_source" 
                                      id="<?php echo $current_field; ?>" 
                                      class="form-control" 
                                      rows="10"><?php echo isset($current_report[$current_field]) ? htmlspecialchars($current_report[$current_field]) : ''; ?></textarea>
                            <input type="hidden" name="<?php echo $current_field; ?>" id="<?php echo $current_field; ?>_hidden" required>
                        </div>
                    <?php endif; ?>

                    <div class="form-group mt-4">
                        <?php if ($current_step > 0): ?>
                            <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
                        <?php endif; ?>

                        <?php if ($current_step === 8): ?>
                            <button type="submit" name="complete" value="true" class="btn btn-success">Complete Report</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Save & Continue</button>
                        <?php endif; ?>
                    </div>
                </form>

    <?php if ($current_step >= 2): ?>
    <script>
        ClassicEditor
            .create(document.querySelector('#<?php echo $current_field; ?>'))
            .then(editor => {
                // Store editor instance
                window.editor = editor;
                
                // Get the form and hidden input
                const form = document.getElementById('reportForm');
                const hiddenInput = document.querySelector('#<?php echo $current_field; ?>_hidden');
                
                // Set initial value if exists
                const initialContent = <?php echo json_encode(isset($current_report[$current_field]) ? $current_report[$current_field] : ''); ?>;
                if (initialContent) {
                    editor.setData(initialContent);
                }
                
                // Update hidden input before form submission
                form.addEventListener('submit', function(e) {
                    const editorContent = editor.getData();
                    if (!editorContent.trim()) {
                        e.preventDefault();
                        alert('Please fill out this field.');
                        return false;
                    }
                    hiddenInput.value = editorContent;
                });
                
                // Set initial value for hidden input
                hiddenInput.value = editor.getData();
                
                // Update hidden input whenever editor content changes
                editor.model.document.on('change:data', () => {
                    hiddenInput.value = editor.getData();
                });
            })
            .catch(error => {
                console.error('CKEditor initialization error:', error);
            });
    </script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>