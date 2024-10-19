<?php
session_start();
require('fpdf/fpdf.php');  // Make sure to include the FPDF library

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

// Handle form submission for adding an expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['expense_name'])) {
    $expense_name = mysqli_real_escape_string($conn, $_POST['expense_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $account_name = mysqli_real_escape_string($conn, $_POST['account_name']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);

    $query_insert = "INSERT INTO expenses (expense_name, description, account_name, amount, created_at) VALUES ('$expense_name', '$description', '$account_name', '$amount', NOW())";
    mysqli_query($conn, $query_insert);
    header("Location: expenses.php");
    exit;
}

// Handle expense deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    $query_delete = "DELETE FROM expenses WHERE id = '$delete_id'";
    mysqli_query($conn, $query_delete);
    header("Location: expenses.php");
    exit;
}

// Handle expense editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = mysqli_real_escape_string($conn, $_POST['edit_id']);
    $expense_name = mysqli_real_escape_string($conn, $_POST['expense_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $account_name = mysqli_real_escape_string($conn, $_POST['account_name']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);

    $query_update = "UPDATE expenses SET expense_name='$expense_name', description='$description', account_name='$account_name', amount='$amount' WHERE id='$edit_id'";
    mysqli_query($conn, $query_update);
    header("Location: expenses.php");
    exit;
}


// Handle PDF export
if (isset($_GET['export_pdf'])) {
    // Fetch all expenses
    $query_all_expenses = "SELECT * FROM expenses ORDER BY created_at DESC";
    $result_all_expenses = mysqli_query($conn, $query_all_expenses);

    // Create new PDF instance
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('Arial', 'B', 16);

    // Title
    $pdf->Cell(0, 10, 'Expenses Report (KES)', 0, 1, 'C');
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(50, 10, 'Expense Name', 1, 0, 'C', true);
    $pdf->Cell(80, 10, 'Description', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Account', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Amount (KES)', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Date', 1, 0, 'C', true);
    $pdf->Ln();

    // Table content
    $pdf->SetFont('Arial', '', 10);
    $total_amount = 0;

    // Define colors for different accounts
    $account_colors = [
        'Cash' => [230, 230, 250],  // Lavender
        'Bank' => [220, 255, 220],  // Light Green
        'Credit Card' => [255, 228, 225],  // Misty Rose
        // Add more accounts and colors as needed
    ];

    while ($row = mysqli_fetch_assoc($result_all_expenses)) {
        // Set color based on account
        if (isset($account_colors[$row['account_name']])) {
            $pdf->SetFillColor($account_colors[$row['account_name']][0], $account_colors[$row['account_name']][1], $account_colors[$row['account_name']][2]);
        } else {
            $pdf->SetFillColor(255, 255, 255);  // White for unknown accounts
        }

        $pdf->Cell(50, 10, $row['expense_name'], 1, 0, 'L', true);
        
        // Handle long descriptions
        $description = $row['description'];
        if (strlen($description) > 50) {
            $description = substr($description, 0, 47) . '...';
        }
        $pdf->Cell(80, 10, $description, 1, 0, 'L', true);
        
        $pdf->Cell(40, 10, $row['account_name'], 1, 0, 'L', true);
        $pdf->Cell(30, 10, number_format($row['amount'], 2), 1, 0, 'R', true);
        $pdf->Cell(40, 10, date('Y-m-d', strtotime($row['created_at'])), 1, 0, 'C', true);
        $pdf->Ln();

        $total_amount += $row['amount'];
    }

    // Total row
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(170, 10, 'Total', 1, 0, 'R', true);
    $pdf->Cell(30, 10, number_format($total_amount, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 10, '', 1, 0, 'C', true);

    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->PageNo() . '/{nb}', 0, 0, 'C');

    // Output PDF
    $pdf->AliasNbPages();
    $pdf->Output('expenses_report.pdf', 'D');
    exit;
}

// Initialize variables for search, filter, and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // Number of expenses per page
$offset = ($page - 1) * $limit;

// Build the SQL query with search conditions
$query_expenses = "SELECT * FROM expenses WHERE 1=1";

if ($search) {
    $query_expenses .= " AND (expense_name LIKE '%$search%' OR description LIKE '%$search%' OR account_name LIKE '%$search%' OR amount LIKE '%$search%')";
}

$query_expenses .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result_expenses = mysqli_query($conn, $query_expenses);

// Get total number of expenses for pagination
$query_total = "SELECT COUNT(*) as total FROM expenses WHERE 1=1";

if ($search) {
    $query_total .= " AND (expense_name LIKE '%$search%' OR description LIKE '%$search%' OR account_name LIKE '%$search%' OR amount LIKE '%$search%')";
}

$result_total = mysqli_query($conn, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_pages = ceil($row_total['total'] / $limit);

// Get accounts for the dropdown
$query_accounts = "SELECT name FROM accounts";
$result_accounts = mysqli_query($conn, $query_accounts);
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

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>

    <style>
        /* Your custom CSS here */
    </style>
</head>

<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <?php include 'header.php'; ?>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Manage Expenses</h3>
                    <ul class="breadcrumbs mb-3">
                        <li class="nav-home">
                            <a href="#">
                                <i class="icon-home"></i>
                            </a>
                        </li>
                        <li class="separator">
                            <i class="icon-arrow-right"></i>
                        </li>
                        <li class="nav-item">
                            <a href="#">Expenses</a>
                        </li>
                    </ul>
                </div>

                <!-- Add Expense Button -->
                <button class="btn btn-primary mb-3" onclick="addExpense()">Add Expense</button>

                <!-- Export PDF Button -->
                <a href="?export_pdf=1" class="btn btn-success mb-3 ml-2">Export to PDF</a>

                <!-- Search Form -->
                <form method="GET" action="">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by Name, Description, Account, or Amount" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Expenses List</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="display table table-striped table-hover">
                                        <thead>
                                        <tr>
                                            <th>Expense Name</th>
                                            <th>Description</th>
                                            <th>Account</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        if (mysqli_num_rows($result_expenses) > 0) {
                                            while ($row = mysqli_fetch_assoc($result_expenses)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['expense_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['account_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                                echo "<td>
                                                        <button class='btn btn-sm btn-primary' onclick='editExpense(" . json_encode($row) . ")'>Edit</button>
                                                        <button class='btn btn-sm btn-danger' onclick='deleteExpense(" . $row['id'] . ")'>Delete</button>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6'>No expenses found</td></tr>";
                                        }
                                        ?>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th>Expense Name</th>
                                            <th>Description</th>
                                            <th>Account</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Pagination Links -->
                                <nav aria-label="Page navigation example">
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        for ($i = 1; $i <= $total_pages; $i++) {
                                            echo "<li class='page-item " . ($i == $page ? 'active' : '') . "'><a class='page-link' href='?page=$i&search=$search'>$i</a></li>";
                                        }
                                        ?>
                                    </ul>
                                </nav>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            function addExpense() {
                Swal.fire({
                    title: 'Add New Expense',
                    html:
                        '<form id="expenseForm" method="POST">' +
                        '<input type="text" name="expense_name" class="form-control mb-3" placeholder="Expense Name" required>' +
                        '<textarea name="description" class="form-control mb-3" placeholder="Description" required></textarea>' +
                        '<input type="number" step="0.01" name="amount" class="form-control mb-3" placeholder="Amount" required>' +
                        '<select name="account_name" class="form-control mb-3" required>' +
                        '<option value="" disabled selected>Select Account</option>' +
                        <?php while ($account = mysqli_fetch_assoc($result_accounts)) {
                        echo "'<option value=\"" . htmlspecialchars($account['name']) . "\">" . htmlspecialchars($account['name']) . "</option>' +";
                    } ?>
                        '</select>' +
                        '</form>',
                    showCancelButton: true,
                    confirmButtonText: 'Add',
                    preConfirm: () => {
                        document.getElementById('expenseForm').submit();
                    }
                });
            }

            function editExpense(expense) {
                Swal.fire({
                    title: 'Edit Expense',
                    html:
                        '<form id="editExpenseForm" method="POST">' +
                        '<input type="hidden" name="edit_id" value="' + expense.id + '">' +
                        '<input type="text" name="expense_name" class="form-control mb-3" placeholder="Expense Name" value="' + expense.expense_name + '" required>' +
                        '<textarea name="description" class="form-control mb-3" placeholder="Description" required>' + expense.description + '</textarea>' +
                        '<input type="number" step="0.01" name="amount" class="form-control mb-3" placeholder="Amount" value="' + expense.amount + '" required>' +
                        '<select name="account_name" class="form-control mb-3" required>' +
                        '<option value="" disabled>Select Account</option>' +
                        <?php
                        mysqli_data_seek($result_accounts, 0);
                        while ($account = mysqli_fetch_assoc($result_accounts)) {
                            echo "'<option value=\"" . htmlspecialchars($account['name']) . "\"' + ('" . htmlspecialchars($account['name']) . "' === expense.account_name ? ' selected' : '') + '>" . htmlspecialchars($account['name']) . "</option>' +";
                        }
                        ?>
                        '</select>' +
                        '</form>',
                    showCancelButton: true,
                    confirmButtonText: 'Update',
                    preConfirm: () => {
                        document.getElementById('editExpenseForm').submit();
                    }
                });
            }

            function deleteExpense(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = '<input type="hidden" name="delete_id" value="' + id + '">';
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        </script>
    </div>
</div>
</body>
</html>