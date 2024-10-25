<?php
require 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start the session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get recent payments (limit to 5)
$payments_sql = "SELECT trans_id, amount, created_at FROM payments ORDER BY created_at DESC LIMIT 5";
$payments_result = $conn->query($payments_sql);

// Get recent withdrawals (limit to 5)
$withdrawals_sql = "SELECT transaction_id, transaction_amount, transaction_completed_datetime FROM withdrawals ORDER BY transaction_completed_datetime DESC LIMIT 5";
$withdrawals_result = $conn->query($withdrawals_sql);

$transactions = [];

// Add payments to transactions array
if ($payments_result->num_rows > 0) {
    while($row = $payments_result->fetch_assoc()) {
        $transactions[] = [
            'type' => 'Payment',
            'id' => $row['trans_id'],
            'amount' => $row['amount'],
            'date' => $row['created_at']
        ];
    }
}

// Add withdrawals to transactions array
if ($withdrawals_result->num_rows > 0) {
    while($row = $withdrawals_result->fetch_assoc()) {
        $transactions[] = [
            'type' => 'Withdrawal',
            'id' => $row['transaction_id'],
            'amount' => $row['transaction_amount'],
            'date' => $row['transaction_completed_datetime']
        ];
    }
}

// Return JSON response (no output should have occurred before this point)
if (!headers_sent()) {
    header('Content-Type: application/json');
    echo json_encode($transactions);
    $conn->close();
    exit(); // Terminate script execution after sending the JSON response
}

// Proceed with the rest of the page rendering (only happens if the JSON response was not needed)

// Get user details from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'user@example.com';
$user_avatar = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'assets/img/profile.jpg'; // Use default if not set
?>
<!-- HTML content starts here -->
<div class="main-header">
    <div class="main-header-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="index.html" class="logo">
                <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->
    </div>
  
    <!-- Navbar Header -->
    <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
        <div class="container-fluid">
            <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button type="submit" class="btn btn-search pe-1">
                            <i class="fa fa-search search-icon"></i>
                        </button>
                    </div>
                    <input type="text" placeholder="Search ..." class="form-control" />
                </div>
            </nav>

            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <!-- Notification Trigger Area -->
                <li class="nav-item topbar-icon dropdown hidden-caret">
    <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-bell"></i>
        <span class="notification" id="notification-count">0</span>
    </a>
    <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
        <li>
            <div class="dropdown-title" id="notification-title">Recent Payments & Withdrawals</div>
        </li>
        <li>
            <div class="notif-scroll scrollbar-outer">
                <div class="notif-center" id="notification-list">
                    <!-- Recent transactions will be dynamically inserted here -->
                </div>
            </div>
        </li>
        <li>
            <a class="see-all" href="javascript:void(0);">See all notifications
                <i class="fa fa-angle-right"></i>
            </a>
        </li>
    </ul>
</li>

                <li class="nav-item topbar-user dropdown hidden-caret">
                    <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <div class="avatar-sm">
                            <img src="<?php echo $user_avatar; ?>" alt="..." class="avatar-img rounded-circle" />
                        </div>
                        <span class="profile-username">
                            <span class="op-7">Hi,</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($user_name); ?></span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-user animated fadeIn">
                        <div class="dropdown-user-scroll scrollbar-outer">
                            <li>
                                <div class="user-box">
                                    <div class="avatar-lg">
                                        <img src="<?php echo $user_avatar; ?>" alt="image profile" class="avatar-img rounded" />
                                    </div>
                                    <div class="u-text">
                                        <h4><?php echo htmlspecialchars($user_name); ?></h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($user_email); ?></p>
                                        <a href="#" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">Logout</a>
                            </li>
                        </div>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <!-- End Navbar -->
</div>

<script>
document.getElementById('notifDropdown').addEventListener('click', function() {
    // Perform an AJAX request to fetch recent transactions (payments & withdrawals)
    fetch('notification.php')
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('notification-list');
            const notificationCount = document.getElementById('notification-count');
            
            // Clear the current notifications
            notificationList.innerHTML = '';
            
            // Check if there are transactions to display
            if (data.length > 0) {
                // Update the notification count
                notificationCount.textContent = data.length;

                // Loop through the transactions and add them to the notification list
                data.forEach(transaction => {
                    const notifIcon = transaction.type === 'Payment' ? 'fa-money' : 'fa-exchange';
                    const transactionDate = transaction.date === "0000-00-00 00:00:00" 
                        ? "Date not available" 
                        : new Date(transaction.date).toLocaleString();
                        
                    const transactionItem = `
                        <a href="#">
                            <div class="notif-icon notif-primary">
                                <i class="fa ${notifIcon}"></i>
                            </div>
                            <div class="notif-content">
                                <span class="block">${transaction.type}: ${transaction.amount}</span>
                                <span class="time">${transactionDate}</span>
                            </div>
                        </a>
                    `;
                    notificationList.innerHTML += transactionItem;
                });
            } else {
                // If no transactions, show a "No recent transactions" message
                notificationList.innerHTML = '<p class="text-center">No recent transactions</p>';
                notificationCount.textContent = '0';
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
});
</script>