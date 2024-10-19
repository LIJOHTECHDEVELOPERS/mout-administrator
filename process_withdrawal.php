<?php
session_start();
include 'db.php';  // Include your database connection

// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Required M-Pesa API credentials
$consumer_key = '2RxLaz9AVMrdb3vHq9MVLpMxbrKd55IDpDSzG8dGvoj6mQti';
$consumer_secret = 'lZwDUKwKJnjVkGmDb9vBOLjY17X06hYhHEWDmYq5tlOgPAlZF2tzpt4r5swdQKy6';
$initiator_name = 'Alexnjoroge';
$security_credential = 'UNIJQnVkJcztRQf0S3WvDLPuxVhU0d5LYw73KTyeTpksKwXydicxQ3VBJIL90hDn2wdORg8xC3ANgnNJOPWBPFxBOv30208aaDufU5KRkfwvvauJsAbmDuZNkabwdzPBMBGEmbS7pUzFmVDvYrELU2U5j1GWi+/i//Px4BEYyZHLANhsr+ueDOorQcZnT+IC3QDYEbqI7pLcCg4h52kOfkJJ8MR1r/l+8w+L272OX9MyOYCcLlv5Oys0ToOHm1SG1m47J9FawLAfD1UrU0WhknjanIpErdCRHfDQgc+a/ISvXN58EsKZ6gZIycq1fPqzve2wleKjE6O5MwrtHfnoEw=='; // use your real security credential
$command_id = 'BusinessPayment';
$party_a = '4131985';
$queue_timeout_url = 'https://dash.moutjkuatministry.cloud/timeout_url.php';
$result_url = 'https://dash.moutjkuatministry.cloud/result_url.php';
// Helper functions (Assumed already present in your script)
function getAccessToken($consumer_key, $consumer_secret) {
    // Fetch token (same as original)
}

function initiateB2CTransaction($access_token, $initiator_name, $security_credential, $command_id, $amount, $party_a, $party_b, $remarks, $queue_timeout_url, $result_url) {
    // Initiate B2C transaction (same as original)
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Get the withdrawal data
$accountId = $_POST['accountId'] ?? null;
$amount = $_POST['amount'] ?? null;
$phone = $_POST['phone'] ?? null; // Get phone number from the request

if ($accountId && $amount && is_numeric($amount) && $amount > 0 && $phone) {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Fetch updated_balance from the database
        $balanceStmt = $conn->prepare("SELECT updated_balance, name FROM accounts WHERE id = ?");
        $balanceStmt->bind_param("i", $accountId);
        $balanceStmt->execute();
        $balanceStmt->bind_result($updatedBalance, $accountName);
        $balanceStmt->fetch();
        $balanceStmt->close();

        if ($updatedBalance === null) {
            echo json_encode(['success' => false, 'message' => 'Account not found']);
            exit();
        }

        // Check if sufficient balance exists
        if ($updatedBalance >= $amount) {
            // Update the account balance in the database
            $newBalance = $updatedBalance - $amount;
            $updateStmt = $conn->prepare("UPDATE accounts SET updated_balance = ? WHERE id = ?");
            $updateStmt->bind_param("di", $newBalance, $accountId);
            $updateStmt->execute();

            // Insert into expenses table
            $expenseStmt = $conn->prepare("INSERT INTO expenses (expense_name, description, account_name, amount) VALUES (?, ?, ?, ?)");
            $expenseDescription = "Withdrawal processed";
            $expenseStmt->bind_param("sssd", $accountName, $expenseDescription, $accountName, $amount);
            $expenseStmt->execute();

            // Initiate B2C transaction
            $access_token = getAccessToken($consumer_key, $consumer_secret);
            $b2cResponse = initiateB2CTransaction($access_token, $initiator_name, $security_credential, $command_id, $amount, $party_a, $phone, 'B2C payment', $queue_timeout_url, $result_url);

            // Check if the response is valid JSON
            if ($b2cResponse !== false) {
                // Decode the B2C response
                $b2cResponseData = json_decode($b2cResponse, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON response from B2C: " . json_last_error_msg());
                }

                // Insert into transactions table
                $transactionStmt = $conn->prepare("INSERT INTO transactions (transaction_amount, transaction_id, created_at) VALUES (?, ?, NOW())");
                $transactionId = $b2cResponseData['TransactionID'] ?? null; // Assuming TransactionID is part of the response
                $transactionStmt->bind_param("ds", $amount, $transactionId);
                $transactionStmt->execute();

                // Commit transaction
                $conn->commit();

                echo json_encode(['success' => true, 'message' => 'Withdrawal processed successfully']);
            } else {
                throw new Exception("B2C response is empty or false.");
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error processing withdrawal: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>
