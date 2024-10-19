<?php
session_start();
include('db.php');
include('report_functions.php');

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

// Function to check if user has required role
function hasRole($requiredRole) {
    global $userRole;
    return $userRole === $requiredRole;
}

// Function to get family ID for familyadmin
function getFamilyId($userId) {
    global $conn;
    // Get the family_id from family_assignments table based on the logged-in user's ID
    $stmt = $conn->prepare("SELECT family_id FROM family_assignments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['family_id'];
    }
    return null;
}

// Function to get the account ID for the family
function getAccountIdByFamily($familyId) {
    global $conn;
    // Get the account_id from families table where the family_id matches
    $stmt = $conn->prepare("SELECT account_id FROM families WHERE id = ?");
    $stmt->bind_param("i", $familyId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['account_id'];
    }
    return null;
}

// Function to get account details based on account_id
function getAccountDetails($accountId) {
    global $conn;
    // Fetch account details from accounts table
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Initialize variables for account visibility
$familyId = null;
$accountId = null;
$accountDetails = null;
$visibleAccounts = [];

// If the user is a familyadmin, get their family account only
if (hasRole('familyadmin')) {
    $familyId = getFamilyId($userId);
    
    if ($familyId) {
        // Get the account ID tied to the family
        $accountId = getAccountIdByFamily($familyId);
        
        if ($accountId) {
            // Fetch account details and limit the view to this specific account
            $accountDetails = getAccountDetails($accountId);
            if ($accountDetails) {
                $visibleAccounts[] = $accountDetails; // Add the account details to the visible accounts array
            }
        }
    }
} elseif (hasRole('admin')) {
    // Admin should see all accounts
    $query = "SELECT * FROM accounts";
    $result = $conn->query($query);
    $visibleAccounts = $result->fetch_all(MYSQLI_ASSOC);  // Admin can see all accounts
}

// Convert visible accounts to JSON for JavaScript use
$visibleAccountsJson = json_encode($visibleAccounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    
    <!-- Web Font -->
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
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/withdrawal.css" />
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <?php include 'header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <div class="container">
                        <h1>Withdrawal</h1>
                        
                        <div class="account-cards" id="accountCards">
                            <!-- Account cards will be dynamically inserted here -->
                        </div>
                        
                        <div class="withdrawal-form">
                            <form id="withdrawalForm">
                                <div class="form-group">
                                    <label for="accountSelect">Select Account</label>
                                    <select id="accountSelect" required>
                                        <!-- Options will be dynamically populated -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="withdrawalAmount">Withdrawal Amount</label>
                                    <input type="number" id="withdrawalAmount" required min="0" step="0.01">
                                </div>
                                <div class="form-group">
                                    <label for="withdrawalPhone">Phone Number</label>
                                    <input type="text" id="withdrawalPhone" required>
                                </div>
                                <button type="submit">Withdraw</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Pass the visible accounts data to JavaScript
        var visibleAccounts = <?php echo $visibleAccountsJson; ?>;
    </script>
    <script src="assets/js/withdrawal.js"></script>
    <!-- JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
