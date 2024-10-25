<?php
require 'db.php';

header('Content-Type: application/json');

// Fetch recent payments
$payments_sql = "SELECT trans_id, amount, created_at FROM payments ORDER BY created_at DESC LIMIT 5";
$payments_result = $conn->query($payments_sql);

// Fetch recent withdrawals
$withdrawals_sql = "SELECT transaction_id, transaction_amount, transaction_completed_datetime FROM withdrawals ORDER BY transaction_completed_datetime DESC LIMIT 5";
$withdrawals_result = $conn->query($withdrawals_sql);

$transactions = [];

// Process payments
if ($payments_result->num_rows > 0) {
    while ($row = $payments_result->fetch_assoc()) {
        $transactions[] = [
            'type' => 'Payment',
            'id' => $row['trans_id'],
            'amount' => $row['amount'],
            'date' => $row['created_at']
        ];
    }
}

// Process withdrawals
if ($withdrawals_result->num_rows > 0) {
    while ($row = $withdrawals_result->fetch_assoc()) {
        $transactions[] = [
            'type' => 'Withdrawal',
            'id' => $row['transaction_id'],
            'amount' => $row['transaction_amount'],
            'date' => $row['transaction_completed_datetime']
        ];
    }
}

// Send JSON response
echo json_encode($transactions);
$conn->close();
