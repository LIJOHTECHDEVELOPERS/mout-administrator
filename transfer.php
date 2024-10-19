<?php
include 'db.php';  // Your database connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromAccountId = $_POST['from_account_id'];
    $toAccountId = $_POST['to_account_id'];
    $amount = $_POST['amount'];

    // Check if transferring to the same account
    if ($fromAccountId == $toAccountId) {
        echo json_encode(['success' => false, 'error' => 'Cannot transfer to the same account.']);
        exit;
    }

    try {
        // Start a transaction
        mysqli_begin_transaction($conn);

        // Check if the from account has sufficient balance
        $checkBalanceStmt = $conn->prepare("SELECT updated_balance FROM accounts WHERE id = ? FOR UPDATE");
        $checkBalanceStmt->bind_param("i", $fromAccountId);
        $checkBalanceStmt->execute();
        $result = $checkBalanceStmt->get_result();
        $fromAccount = $result->fetch_assoc();

        if ($fromAccount && $fromAccount['updated_balance'] >= $amount) {
            // Check if the to account exists
            $checkToAccountStmt = $conn->prepare("SELECT id FROM accounts WHERE id = ? FOR UPDATE");
            $checkToAccountStmt->bind_param("i", $toAccountId);
            $checkToAccountStmt->execute();
            $toAccountResult = $checkToAccountStmt->get_result();
            
            if ($toAccountResult->num_rows == 0) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'error' => 'Recipient account does not exist.']);
                exit;
            }

            // Deduct amount from the sender's account
            $deductStmt = $conn->prepare("UPDATE accounts SET updated_balance = updated_balance - ? WHERE id = ?");
            $deductStmt->bind_param("di", $amount, $fromAccountId);
            $deductStmt->execute();

            // Add amount to the receiver's account
            $addStmt = $conn->prepare("UPDATE accounts SET updated_balance = updated_balance + ? WHERE id = ?");
            $addStmt->bind_param("di", $amount, $toAccountId);
            $addStmt->execute();

            // Log the transfer
            $transferStmt = $conn->prepare("INSERT INTO transfers (from_account_id, to_account_id, amount) VALUES (?, ?, ?)");
            $transferStmt->bind_param("iid", $fromAccountId, $toAccountId, $amount);
            $transferStmt->execute();

            // Commit the transaction
            mysqli_commit($conn);

            echo json_encode(['success' => true, 'message' => 'Transfer successful.']);
        } else {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'error' => 'Insufficient balance.']);
        }
    } catch (mysqli_sql_exception $e) {
        // Rollback the transaction in case of error
        mysqli_rollback($conn);
        error_log('Transfer error: ' . $e->getMessage());  // Log the error
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>