<?php
session_start();
include('db.php');

// Get the POST data from M-Pesa
$transactionData = json_decode(file_get_contents('php://input'), true);

// Log the transaction response for debugging (optional)
file_put_contents('b2c_success.log', print_r($transactionData, true), FILE_APPEND);

// Check if the response contains the necessary fields
if (isset($transactionData['Body']['stkCallback']['ResultCode']) && $transactionData['Body']['stkCallback']['ResultCode'] == 0) {
    $transactionId = $transactionData['Body']['stkCallback']['MerchantRequestID']; // Adjust as per the response structure
    $amount = $transactionData['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
    $phoneNumber = $transactionData['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
    
    // Update the transaction record in your database
    $updateStmt = $conn->prepare("UPDATE transactions SET transaction_completed_datetime = NOW() WHERE transaction_id = ?");
    $updateStmt->bind_param("s", $transactionId);
    $updateStmt->execute();

    // Optionally: Notify user of successful transaction
    // You can use iziToast or any other method here
    // iziToast.success({ title: 'Success', message: 'Transaction completed successfully!' });
} else {
    // Handle failure cases
    // You may log this response or update the transaction as failed
    file_put_contents('b2c_failure.log', print_r($transactionData, true), FILE_APPEND);
}

http_response_code(200); // Respond with a 200 OK
?>
