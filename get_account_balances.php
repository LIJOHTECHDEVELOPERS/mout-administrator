<?php
require 'db_connection.php';

$response = [
    'totalBalance' => 0,
    'offering' => 0,
    'welfare' => 0,
    'sales' => 0,
    'expenses' => 0,
    'sound' => 0
];

// Query initial balances from manually created accounts
$accountQuery = "SELECT SUM(initial_balance) as totalInitial FROM accounts";
$accountResult = $db->query($accountQuery);
if ($accountRow = $accountResult->fetch_assoc()) {
    $response['totalBalance'] += $accountRow['totalInitial'];
}

// Query M-Pesa payments by account references
$mpesaQuery = "SELECT account_reference, SUM(amount) as total FROM payments GROUP BY account_reference";
$mpesaResult = $db->query($mpesaQuery);

while ($mpesaRow = $mpesaResult->fetch_assoc()) {
    switch (strtolower($mpesaRow['account_reference'])) {
        case 'offering':
        case 'offerings':
            $response['offering'] += $mpesaRow['total'];
            break;
        case 'welfare':
            $response['welfare'] += $mpesaRow['total'];
            break;
        case 'sales':
            $response['sales'] += $mpesaRow['total'];
            break;
        case 'expenses':
            $response['expenses'] += $mpesaRow['total'];
            break;
        case 'sound':
            $response['sound'] += $mpesaRow['total'];
            break;
    }
    $response['totalBalance'] += $mpesaRow['total'];
}

echo json_encode($response);
?>
