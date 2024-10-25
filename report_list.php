<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "SELECT * FROM admin_users WHERE id = $user_id");
if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    echo "User not found.";
    exit();
}

// Fetch all reports with the most recent data
$query = "SELECT r.*, u.name as user_name, d.name as docket_name, s.year as spiritual_year
          FROM reports r
          JOIN admin_users u ON r.user_id = u.id
          JOIN dockets d ON r.docket_id = d.id
          JOIN spiritual_years s ON r.spiritual_year_id = s.id
          ORDER BY r.updated_at DESC";  // Changed from created_at to updated_at

$reports_result = mysqli_query($conn, $query);

if (!$reports_result) {
    echo "Error fetching reports: " . mysqli_error($conn);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <?php include 'header.php'; ?>
        <div class="container">
            <div class="page-inner">
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
                                <th>Last Updated</th>
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
                                    <td><?php echo ucfirst($report['status']); ?></td>
                                    <td><?php echo htmlspecialchars($report['user_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($report['updated_at'])); ?></td>
                                    <td>
                                        <a href="preview_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-warning">Preview</a>
                                        <?php if ($report['user_id'] == $user_id) { ?>
                                            <a href="edit_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-primary">Edit</a>
                                            <?php if ($report['status'] === 'completed') { ?>
                                                <a href="download_report.php?report_id=<?php echo $report['id']; ?>" class="btn btn-success">Download PDF</a>
                                            <?php } ?>
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
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- JS Scripts -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>