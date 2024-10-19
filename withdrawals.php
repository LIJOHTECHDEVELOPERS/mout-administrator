<?php
session_start();
include 'db.php';  // Include your database connection

// Turn on error reporting (should be disabled in production after testing)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Query to get total number of withdrawals
$count_query = "SELECT COUNT(*) as total FROM withdrawals";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Query to get withdrawal records with user information and account details
$query = "SELECT w.transaction_amount, w.transaction_receipt AS transaction_id, 
                 w.receiver_party_public_name, w.created_at AS date_initiated, 
                 u.name AS user_name, a.name AS account_name
          FROM withdrawals w
          JOIN admin_users u ON u.id = w.initiated_by_user_id
          JOIN accounts a ON a.id = w.account_debited  -- Assuming this column holds the ID of the debited account
          ORDER BY w.created_at DESC
          LIMIT $offset, $records_per_page";

$result = $conn->query($query);
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
    <style>
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <?php include 'header.php'; ?>
        <div class="container-fluid py-4">
        <h2 class="mb-4">Withdrawal Transactions</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th> <!-- Numbering Column -->
                    <th>User</th>
                    <th>Phone</th>
                    <th>Account Debited</th> <!-- New Column for Account Debited -->
                    <th>Amount</th>
                    <th>Transaction ID</th>
                    <th>Date Initiated</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result->num_rows > 0) {
                    $counter = $offset + 1; // Start numbering from the correct offset
                    while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td> <!-- Incrementing counter for numbering -->
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['receiver_party_public_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['account_name']); ?></td> <!-- Displaying account name -->
                        <td><?php echo number_format($row['transaction_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['date_initiated'])); ?></td>
                    </tr>
                    <?php endwhile; 
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No withdrawal transactions found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>
