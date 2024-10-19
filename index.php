<?php
session_start();
include('db.php'); // Ensure db.php includes your MySQLi connection setup

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

// Fetch total members count
$query_members_count = "SELECT COUNT(*) AS total_members FROM members"; // Replace 'members' with your actual table name
$result_count_members = mysqli_query($conn, $query_members_count);

if ($result_count_members) {
    $row_count = mysqli_fetch_assoc($result_count_members);
    $total_count = $row_count['total_members'];
} else {
    die('Query Error (members count): ' . mysqli_error($conn));
}
mysqli_free_result($result_count_members);


// Fetch total users count
$query_users_count = "SELECT COUNT(*) AS total_users FROM users"; // Replace 'users' with your actual table name
$result_count_users = mysqli_query($conn, $query_users_count);

if ($result_count_users) {
    $row_users = mysqli_fetch_assoc($result_count_users);
    $total_users = $row_users['total_users'];
} else {
    die('Query Error (users count): ' . mysqli_error($conn));
}
mysqli_free_result($result_count_users);

// Fetch new members for the sidebar or main content
$query_new_members = "SELECT name, current_year_of_study FROM members ORDER BY created_at DESC LIMIT 8"; // Adjust as needed
$result_members = mysqli_query($conn, $query_new_members);

if (!$result_members) {
    die('Query Error (new members): ' . mysqli_error($conn));
}

// Fetch recent transactions
$query_transactions = "SELECT first_name, trans_id, amount, account_reference, created_at FROM payments ORDER BY created_at DESC LIMIT 6"; // Replace 'transactions' with your actual table name
$result_transactions = mysqli_query($conn, $query_transactions);

$query = "SELECT COUNT(*) AS total_associates FROM associates";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_associates = $row['total_associates'];

if (!$result_transactions) {
    die('Query Error (transactions): ' . mysqli_error($conn));
}
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
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
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <style>
      /* Your custom CSS here */
    </style>
  </head>

  <body>
    <div class="wrapper">
      <!-- Include the sidebar -->
      <?php include 'sidebar.php'; ?>

      <!-- Main Panel -->
  
      <div class="main-panel">
      <?php include 'header.php'; ?>
       <div class="container">
          <div class="page-inner">
                <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                    <div>
                        <h3 class="fw-bold mb-3">Dashboard</h3>
                        <h6 class="op-7 mb-2">Mout Jkuat Administrator Panel</h6>
                    </div>
                    <div class="ms-md-auto py-2 py-md-0">
                        <a href="associates.php" class="btn btn-label-info btn-round me-2">Add Associate</a>
                        <a href="add_member.php" class="btn btn-primary btn-round">Add Member</a>
                    </div>
                </div>
                <div class="row">
                    <!-- Members Card -->
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Members</p>
                                            <h4 class="card-title"><?php echo $total_count; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Associates Card -->
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-info bubble-shadow-small">
                                            <i class="fas fa-user-friends"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Associates</p>
                                            <h4 class="card-title"><?php echo $total_associates; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Accounts Card -->
                    <div class="col-sm-6 col-md-3">
    <div class="card card-stats card-round">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-icon">
                    <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="col col-stats ms-3 ms-sm-0">
                    <div class="numbers">
                        <p class="card-category">Accounts</p>
                        <h4 class="card-title"><span id="dashboardTotalBalance">0.00</span></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                    <!-- Order Card -->
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Families</p>
                                            <h4 class="card-title">5</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Charts and Statistics -->
                <div class="row">
                    <!-- Accounts Statistics Chart -->
                    <!-- Other content -->
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">Members Statistics</div>
                        <div class="card-tools">
                            <a href="#" class="btn btn-label-success btn-round btn-sm me-2">
                                <span class="btn-label">
                                    <i class="fa fa-pencil"></i>
                                </span>
                                Export
                            </a>
                            <a href="#" class="btn btn-label-info btn-round btn-sm">
                                <span class="btn-label">
                                    <i class="fa fa-print"></i>
                                </span>
                                Print
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="min-height: 375px">
                        <canvas id="statisticsChart"></canvas>
                    </div>
                    <div id="myChartLegend"></div>
              
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch('functions/fetch_members_stats.php')
            .then(response => response.json())
            .then(data => {
                console.log(data);
                renderChart(data);
            })
            .catch(error => {
                console.error('Error fetching member statistics:', error);
            });
    });
    $(document).ready(function () {
    loadAccounts();  // Load accounts and update balances when the document is ready
});

function loadAccounts() {
    $.ajax({
        url: 'get_accounts.php',
        method: 'GET',
        dataType: 'json',
        success: function (accounts) {
            console.log('Accounts:', accounts);  // Check data
            updateAccountCards(accounts);  // Update accounts module
            updateDashboardTotalBalance(accounts);  // Update dashboard total balance
        },
        error: function () {
            Swal.fire('Error', 'Failed to load accounts', 'error');
        }
    });
}

// Update the dashboard card total balance
function updateDashboardTotalBalance(accounts) {
    let totalBalance = 0;

    accounts.forEach(account => {
        totalBalance += parseFloat(account.balance);
    });

    // Check if the dashboard total balance element is available
    if ($('#dashboardTotalBalance').length > 0) {
        $('#dashboardTotalBalance').text(totalBalance.toFixed(2));  // Update dashboard total balance
    } else {
        console.error('Dashboard total balance element not found');
    }
}
function updateAccountCards(accounts) {
    const container = $('#accountCardsContainer');
    container.empty();  // Clear previous cards

    let totalBalance = 0;  // Initialize total balance

    if (accounts.length === 0) {
        container.append('<p>No accounts available</p>');
        return;
    }

    accounts.forEach(account => {
        // Accumulate the balance of each account
        totalBalance += parseFloat(account.balance);

        // Build each card with all necessary information
        const card = `
            <div class="col">
                <div class="card-stats">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="${account.icon}"></i>
                        </div>
                        <p class="card-category">${account.name}</p>
                        <h3 class="card-title">Ksh ${parseFloat(account.balance).toFixed(2)}</h3>
                    </div>
                </div>
            </div>
        `;

        container.append(card);  // Add card to the container
    });

    // Update total balance card in the accounts module
    $('#totalBalance').text(totalBalance.toFixed(2));

    // Update total balance in the dashboard card
    $('#dashboardTotalBalance').text(totalBalance.toFixed(2));
}

    function renderChart(data) {
        var ctx = document.getElementById('statisticsChart').getContext('2d');

        var chart = new Chart(ctx, {
            type: 'bar', // or 'line', 'pie', etc.
            data: {
                labels: data.years,
                datasets: [
                    {
                        label: 'Active Members',
                        data: data.activeMembers,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Inactive Members',
                        data: data.inactiveMembers,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    </script>
                   <div class="col-md-4">
    <div class="card card-primary card-round">
        <div class="card-header">
            <div class="card-head-row">
                <div class="card-title">Total Expenses</div>
                <div class="card-tools">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-label-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Export
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="#" id="exportXls">Export as Xls</a>
                            <a class="dropdown-item" href="#" id="exportPdf">Export as Pdf</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pb-0">
            <div class="mb-4 mt-2">
                <h1 id="totalExpenses">Ksh 0.00</h1>
            </div>
            <div class="pull-in">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>
    </div>
                <!-- Recent Activities -->
                        <div class="card card-round mt-4">
                            <div class="card-body pb-0">
                                <div class="h1 fw-bold float-end text-primary">+5%</div>
                                <h2 class="mb-2">17</h2>
                                <p class="text-muted">Recent Activities</p>
                                <div class="pull-in sparkline-fix">
                                    <div id="lineChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- New Members and Recent Transactions -->
                <div class="row">
                    <!-- New Members -->
                    <div class="col-md-4">
                        <div class="card card-round">
                            <div class="card-body">
                                <div class="card-head-row card-tools-still-right">
                                    <div class="card-title">New Members</div>
                                    <div class="card-tools">
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-list py-4">
                                    <?php while ($row = mysqli_fetch_assoc($result_members)): ?>
                                    <div class="item-list d-flex align-items-center mb-3">
                                        <div class="avatar">
                                            <!-- Unified Avatar -->
                                            <img src="assets/img/avatar.png" alt="User Avatar" class="avatar-img rounded-circle" />
                                        </div>
                                        <div class="info-user ms-3 flex-grow-1">
                                            <div class="username fw-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                                            <div class="status text-muted">Year: <?php echo htmlspecialchars($row['current_year_of_study']); ?></div>
                                        </div>
                                        <div class="actions">
                                            <button class="btn btn-icon btn-link op-8 me-1" title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8" title="Ban">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                    <?php mysqli_free_result($result_members); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Recent Transactions -->
                    <div class="col-md-8">
                        <div class="card card-round">
                            <div class="card-header">
                                <div class="card-head-row card-tools-still-right">
                                    <div class="card-title">Recent Transactions</div>
                                    <div class="card-tools">
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col">Payment From</th>
                                                <th scope="col">Transaction ID</th>
                                                <th scope="col">Amount</th>
                                                <th scope="col">Account Reference</th>
                                                <th scope="col">Date & Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result_transactions->num_rows > 0) {
                                                while ($row = $result_transactions->fetch_assoc()) {
                                                    echo "<tr>
                                                            <td>Payment from " . htmlspecialchars($row['first_name']) . "</td>
                                                            <td>" . htmlspecialchars($row['trans_id']) . "</td>
                                                            <td>KES " . number_format($row['amount'], 2) . "</td>
                                                            <td>" . htmlspecialchars($row['account_reference']) . "</td>
                                                            <td>" . date('M d, Y, h:i A', strtotime($row['created_at'])) . "</td>
                                                        </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>No recent transactions found.</td></tr>";
                                            }
                                            mysqli_free_result($result_transactions);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        

    </div>

    <!-- JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
  </body>
</html>
    <script>
        $(document).ready(function () {
    // Function to load the total expenses and update the card
    function loadTotalExpenses() {
        $.ajax({
            url: 'get_total_expenses.php', // Your script that returns the total expenses
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.total_expenses !== undefined) {
                    // Update the card with the total expenses
                    $('#totalExpenses').text('Ksh ' + parseFloat(response.total_expenses).toFixed(2));
                } else {
                    // Handle the case where total_expenses is not returned
                    $('#totalExpenses').text('Ksh 0.00');
                }
            },
            error: function () {
                // Handle error if the AJAX call fails
                alert('Failed to load total expenses');
            }
        });
    }

    // Load total expenses when the page loads
    loadTotalExpenses();
});

        $(document).ready(function() {
            // Initialize any plugins or scripts here

            // Example: Initialize DataTables for the transactions table
            $('.table').DataTable({
                "paging": true,
                "searching": true,
                "info": false,
                "lengthChange": false,
                "pageLength": 5,
                "order": [[4, "desc"]], // Order by Date & Time descending
                "language": {
                    "emptyTable": "No recent transactions found."
                }
            });
        });

        // Initialize Sparkline
        $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
            type: "line",
            height: "70",
            width: "100%",
            lineWidth: "2",
            lineColor: "#177dff",
            fillColor: "rgba(23, 125, 255, 0.14)",
        });

        // Initialize other sparkline charts as needed
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>

<script>
    function exportToPDF() {
        $.ajax({
            url: 'get_expenses.php',  // Fetch the expenses data to export
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                // Add data to PDF
                doc.text("Expenses Report", 10, 10);
                let yOffset = 20;
                data.forEach(expense => {
                    doc.text(`Date: ${expense.created_at} - Amount: ${expense.amount}`, 10, yOffset);
                    yOffset += 10;
                });

                doc.save("expenses.pdf");
            },
            error: function () {
                Swal.fire('Error', 'Failed to export data', 'error');
            }
        });
    }

    $('.dropdown-item:contains("Pdf")').click(function() {
        exportToPDF();
    });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>

<script>
    function exportToXLS() {
        $.ajax({
            url: 'get_expenses.php',  // Create this script to return expenses data
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                var ws = XLSX.utils.json_to_sheet(data);
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Expenses");
                XLSX.writeFile(wb, "expenses.xlsx");
            },
            error: function () {
                Swal.fire('Error', 'Failed to export data', 'error');
            }
        });
    }

    $('.dropdown-item:contains("Xls")').click(function() {
        exportToXLS();
    });
</script>

</body>
</html>
<?php
$conn->close();
?>
