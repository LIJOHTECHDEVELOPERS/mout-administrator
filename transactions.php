<?php
session_start();
// Database connection
$conn = mysqli_connect("localhost", "root", "", "leadersportal");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

// Initialize variables for search, filter, and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Increased number of transactions per page
$offset = ($page - 1) * $limit;

// Build the SQL query with search and filter conditions
$query_transactions = "SELECT first_name, trans_id, amount, account_reference, created_at 
                        FROM payments 
                        WHERE 1=1";

if ($search) {
    $query_transactions .= " AND (first_name LIKE ? OR trans_id LIKE ? OR account_reference LIKE ?)";
}

if ($start_date && $end_date) {
    $query_transactions .= " AND DATE(created_at) BETWEEN ? AND ?";
}

$query_transactions .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query_transactions);

if ($search && $start_date && $end_date) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt, "sssssii", $search_param, $search_param, $search_param, $start_date, $end_date, $limit, $offset);
} elseif ($search) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt, "sssii", $search_param, $search_param, $search_param, $limit, $offset);
} elseif ($start_date && $end_date) {
    mysqli_stmt_bind_param($stmt, "ssii", $start_date, $end_date, $limit, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}

mysqli_stmt_execute($stmt);
$result_transactions = mysqli_stmt_get_result($stmt);

// Get total number of transactions for pagination
$query_total = "SELECT COUNT(*) as total FROM payments WHERE 1=1";

if ($search) {
    $query_total .= " AND (first_name LIKE ? OR trans_id LIKE ? OR account_reference LIKE ?)";
}

if ($start_date && $end_date) {
    $query_total .= " AND DATE(created_at) BETWEEN ? AND ?";
}

$stmt_total = mysqli_prepare($conn, $query_total);

if ($search && $start_date && $end_date) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt_total, "sssss", $search_param, $search_param, $search_param, $start_date, $end_date);
} elseif ($search) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt_total, "sss", $search_param, $search_param, $search_param);
} elseif ($start_date && $end_date) {
    mysqli_stmt_bind_param($stmt_total, "ss", $start_date, $end_date);
}

mysqli_stmt_execute($stmt_total);
$result_total = mysqli_stmt_get_result($stmt_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_pages = ceil($row_total['total'] / $limit);

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
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/css/plugins.min.css"/>
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css"/>
    <style>
        .table {
            color: #5a5c69;
        }
        .table thead th {
            border-bottom: 2px solid #e3e6f0;
        }
        .table-hover tbody tr:hover {
            background-color: #f2f4ff;
        }
        .pagination .page-item.active .page-link {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        .search-form {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <?php include 'header.php'; ?>
        <div class="container-fluid py-4">
        <h1 class="h3 mb-2 text-gray-800">Recent Transactions</h1>
        <p class="mb-4">View and manage your recent transactions below.</p>
        
        <!-- Search and Filter Form -->
        <form method="GET" action="" class="mb-4 search-form">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-auto">
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-auto">
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Transaction List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Account Reference</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result_transactions) > 0) {
                                while ($row = mysqli_fetch_assoc($result_transactions)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['trans_id']) . "</td>";
                                    echo "<td>Ksh" . number_format(htmlspecialchars($row['amount']), 2) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['account_reference']) . "</td>";
                                    echo "<td>" . date("M d, Y", strtotime($row['created_at'])) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No transactions found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '">' . $i . '</a></li>';
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>]
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>