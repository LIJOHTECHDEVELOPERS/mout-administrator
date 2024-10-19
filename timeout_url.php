<?php
session_start();
include('db.php');

// Get the POST data from M-Pesa
$timeoutData = json_decode(file_get_contents('php://input'), true);

// Log the timeout response for debugging (optional)
file_put_contents('b2c_timeout.log', print_r($timeoutData, true), FILE_APPEND);

// You may want to update the transaction status as "Timed Out" in your database
if (isset($timeoutData['MerchantRequestID'])) {
    $transactionId = $timeoutData['MerchantRequestID'];
    
    // Update the transaction record in your database as timed out
    $updateStmt = $conn->prepare("UPDATE transactions SET result_code = ?, result_desc = ?, updated_at = NOW() WHERE transaction_id = ?");
    $result_code = 'Timeout'; // Custom message or code
    $result_desc = 'Transaction timed out';
    $updateStmt->bind_param("sss", $result_code, $result_desc, $transactionId);
    $updateStmt->execute();
}

http_response_code(200); // Respond with a 200 OK
?>
