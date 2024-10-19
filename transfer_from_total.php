<?php
// Include database connection
include 'db.php';

// Get POST data
$toAccount = $_POST['to'];
$amount = floatval($_POST['amount']);

// Check if amount is valid
if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
    exit;
}

// Check if the target account exists
$checkAccountQuery = "SELECT balance FROM accounts WHERE id = ?";
$stmt = $conn->prepare($checkAccountQuery);
$stmt->bind_param('i', $toAccount);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Fetch the current balance
    $stmt->bind_result($currentBalance);
    $stmt->fetch();

    // Update the target account balance
    $newBalance = $currentBalance + $amount;
    $updateBalanceQuery = "UPDATE accounts SET balance = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($updateBalanceQuery);
    $stmtUpdate->bind_param('di', $newBalance, $toAccount);

    if ($stmtUpdate->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Funds transferred successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update account balance']);
    }

    $stmtUpdate->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Target account not found']);
}

$stmt->close();
$conn->close();
?>
