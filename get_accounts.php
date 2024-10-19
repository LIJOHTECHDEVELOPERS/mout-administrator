<?php
session_start();  // Start session to access user information
include 'db.php';  // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode([]);
    exit();
}

// Get user role and ID from session
$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

$accounts = [];

// Fetch accounts based on user role
if ($userRole === 'familyadmin') {
    // Get the family_id for the logged-in family admin
    $family_query = "SELECT family_id FROM family_assignments WHERE user_id = ?";
    $stmt = $conn->prepare($family_query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $familyId = $row['family_id'];

        // Query to fetch accounts associated with the family
        $accounts_query = "SELECT a.id, a.name, a.balance, a.icon 
                           FROM accounts a 
                           JOIN families f ON a.id = f.account_id 
                           WHERE f.id = ?";
        $stmt = $conn->prepare($accounts_query);
        $stmt->bind_param("i", $familyId);
        $stmt->execute();
        $accounts_result = $stmt->get_result();
    }
} elseif ($userRole === 'admin') {
    // Admin can see all accounts
    $accounts_query = "SELECT id, name, balance, icon FROM accounts";
    $accounts_result = mysqli_query($conn, $accounts_query);
} else {
    // For other roles, return an empty array
    echo json_encode([]);
    exit();
}

// Process account data
while ($row = $accounts_result->fetch_assoc()) {
    $account_name = $row['name'];

    // Fetch total payments for the account
    $payment_query = "SELECT SUM(amount) as total_payments 
                      FROM payments 
                      WHERE LOWER(account_reference) LIKE LOWER('%$account_name%')";
    $payment_result = mysqli_query($conn, $payment_query);
    $payment_row = mysqli_fetch_assoc($payment_result);
    $total_payments = $payment_row['total_payments'] ?? 0;

    // Fetch total expenses for the account
    $expense_query = "SELECT SUM(amount) as total_expenses 
                      FROM expenses 
                      WHERE LOWER(account_name) LIKE LOWER('%$account_name%')";
    $expense_result = mysqli_query($conn, $expense_query);
    $expense_row = mysqli_fetch_assoc($expense_result);
    $total_expenses = $expense_row['total_expenses'] ?? 0;

    // Fetch total transfers made to this account
    $incoming_transfers_query = "SELECT SUM(amount) as total_incoming_transfers 
                                  FROM transfers 
                                  WHERE to_account_id = {$row['id']}";
    $incoming_result = mysqli_query($conn, $incoming_transfers_query);
    $incoming_transfers_row = mysqli_fetch_assoc($incoming_result);
    $total_incoming_transfers = $incoming_transfers_row['total_incoming_transfers'] ?? 0;

    // Fetch total transfers made from this account
    $outgoing_transfers_query = "SELECT SUM(amount) as total_outgoing_transfers 
                                  FROM transfers 
                                  WHERE from_account_id = {$row['id']}";
    $outgoing_result = mysqli_query($conn, $outgoing_transfers_query);
    $outgoing_transfers_row = mysqli_fetch_assoc($outgoing_result);
    $total_outgoing_transfers = $outgoing_transfers_row['total_outgoing_transfers'] ?? 0;

    // Calculate the total balance: initial balance + total payments - total expenses + incoming transfers - outgoing transfers
    $total_balance = $row['balance'] + $total_payments - $total_expenses + $total_incoming_transfers - $total_outgoing_transfers;

    // Update the updated_balance in the database
    $update_balance_query = "UPDATE accounts SET updated_balance = '$total_balance' WHERE id = {$row['id']}";
    mysqli_query($conn, $update_balance_query);

    // Add account details to the array
    $accounts[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'balance' => $total_balance,  // Use calculated balance
        'icon' => $row['icon']
    ];
}

// Return the accounts data as JSON
header('Content-Type: application/json');
echo json_encode($accounts);
?>
