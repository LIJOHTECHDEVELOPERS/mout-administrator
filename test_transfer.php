<?php
session_start();
include 'db.php';  // Include your database connection

// Turn on error reporting (should be disabled in production after testing)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credentials (replace with your actual values)
$consumer_key = '2RxLaz9AVMrdb3vHq9MVLpMxbrKd55IDpDSzG8dGvoj6mQti';
$consumer_secret = 'lZwDUKwKJnjVkGmDb9vBOLjY17X06hYhHEWDmYq5tlOgPAlZF2tzpt4r5swdQKy6';
$initiator_name = 'Alexnjoroge';
$security_credential = 'mPMRoJbifVtte9Z0UvTmxA+IvXjieboYryGtLZ+xRRJbQI5Ned6GVCiStVFwzRDlDp/p0at3EWcD7Zggk9B14uXJtrlxbft7TjrhFFWrIjtkMqQAgz/V6Ej/4fdfjyy5myrr7Bh8+e9yFXbbdktOO7AQeegiK3IQ64WDkwh2flxF9T52L1WNgg7jkzS1QBftZXCwkD9UC9EnQvQat1znbIN+LU4npo/YM6DRptuoXa1hPTqbpalWdjzyqbHRJ6tBQ5sEzvIpSKxKI6sqMSvtSvaNgUkOpc05CX713x17kED8/6faSlg7T0N8E6I/x3ORs3X9P1Q8I5U3b0ivqQ6ECA==';
$command_id = 'BusinessPayment';
$party_a = '4131985';  // Your short code
$queue_timeout_url = 'https://dash.moutjkuatministry.cloud/timeout_url.php';
$result_url = 'https://dash.moutjkuatministry.cloud/result_url.php';
$treasurer_phone = '254762038149';  // Treasurer's phone number

// Helper function for logging
function logMessage($message) {
    $logFile = '/home/moutjkua/dash.moutjkuatministry.cloud/withdrawal_log.txt';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, $timestamp . ' ' . $message . PHP_EOL, FILE_APPEND);
}

// Function to send WhatsApp message
function send_whatsapp_message($to, $name, $message) {
    $api_url = 'http://34.41.242.25:3000/client/sendMessage/8b29c146-ab7e-4104-9973-5da3bd9bcf5d';
    $formatted_to = str_replace('+', '', $to);

    $personalized_message = str_replace('{{name}}', $name, $message);

    $postData = [
        'chatId' => $formatted_to . '@c.us',
        'contentType' => 'string',
        'content' => $personalized_message,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code == 200 && json_decode($response)->success) ? true : false;
}

// Function to send SMS message
function send_sms_message($phoneNumber, $message) {
    $api_url = 'https://blessedtexts.com/api/sms/v1/sendsms';
    $postData = [
        'api_key' => 'af09ec090e4c42498d52bb2673ff559b',
        'sender_id' => 'Easytext',
        'message' => $message,
        'phone' => str_replace('+', '', $phoneNumber) // Strip '+' from phone number
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    
    // Check if the response is valid and has a 'status' key
    if ($http_code == 200 && is_array($responseData) && isset($responseData['status'])) {
        return $responseData['status'] === 'success';
    }
    
    return false;
}

// Helper function for getting access token
function getAccessToken($consumer_key, $consumer_secret) {
    logMessage("Attempting to get access token");
    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Note: Set to true in production

    $curl_response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($curl_response === false) {
        logMessage("cURL error in getAccessToken: " . curl_error($curl) . " (Error code: " . curl_errno($curl) . ")");
        curl_close($curl);
        return null;
    }

    logMessage("Full access token response: " . $curl_response);

    if ($http_code != 200) {
        logMessage("Failed to get access token. HTTP Code: " . $http_code . ". Response: " . $curl_response);
        curl_close($curl);
        return null;
    }

    $response = json_decode($curl_response);
    curl_close($curl);

    if (isset($response->access_token)) {
        logMessage("Access token obtained successfully");
        return $response->access_token;
    } else {
        logMessage("Access token not found in response");
        return null;
    }
}

// Function to send WhatsApp and SMS notifications
function sendNotifications($conn, $withdrawalId) {
    $stmt = $conn->prepare("SELECT w.transaction_amount, w.receiver_party_public_name, w.transaction_receipt, a.name as account_name 
                            FROM withdrawals w 
                            JOIN accounts a ON w.account_debited = a.id 
                            WHERE w.id = ?");
    $stmt->bind_param("i", $withdrawalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactionData = $result->fetch_assoc();
    $stmt->close();

    if ($transactionData) {
        $amount = $transactionData['transaction_amount'];
        $receiverPhone = $transactionData['receiver_party_public_name'];
        $transactionReceipt = $transactionData['transaction_receipt'];
        $accountName = $transactionData['account_name'];

        // Extract name from phone number (assuming format: "254706400432 - Elijah mwai kibuchi")
        $receiverName = explode(' - ', $receiverPhone)[1] ?? 'Client';

        // Send WhatsApp to the receiver
        $whatsappMessage = "Dear {{name}}, your withdrawal of KES $amount from account $accountName has been processed successfully. Transaction receipt: $transactionReceipt. Thank you for your transaction.";
        send_whatsapp_message($receiverPhone, $receiverName, $whatsappMessage);

        // Send SMS and WhatsApp to the treasurer
        $treasurerMessage = "A withdrawal of KES $amount has been processed for $receiverName from account $accountName. Transaction receipt: $transactionReceipt. Please review.";
        send_sms_message($GLOBALS['treasurer_phone'], $treasurerMessage);
        send_whatsapp_message($GLOBALS['treasurer_phone'], 'Treasurer', $treasurerMessage);

        logMessage("Notifications sent for withdrawal ID: $withdrawalId");
    } else {
        logMessage("Failed to retrieve transaction data for withdrawal ID: $withdrawalId");
    }
}

// Function to initiate B2C transaction
function initiateB2CTransaction($access_token, $initiator_name, $security_credential, $command_id, $amount, $party_a, $party_b, $remarks, $queue_timeout_url, $result_url) {
    logMessage("Initiating B2C transaction");
    $url = 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token));

    $curl_post_data = array(
        'InitiatorName' => $initiator_name,
        'SecurityCredential' => $security_credential,
        'CommandID' => $command_id,
        'Amount' => $amount,
        'PartyA' => $party_a,
        'PartyB' => $party_b,
        'Remarks' => $remarks,
        'QueueTimeOutURL' => $queue_timeout_url,
        'ResultURL' => $result_url,
        'Occassion' => ''
    );

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Note: Set to true in production

    $curl_response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($curl_response === false) {
        logMessage("cURL error in initiateB2CTransaction: " . curl_error($curl) . " (Error code: " . curl_errno($curl) . ")");
        curl_close($curl);
        return null;
    }

    curl_close($curl);

    logMessage("B2C API Response (HTTP " . $http_code . "): " . $curl_response);

    return json_decode($curl_response, true);
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    logMessage("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Get the initiated_by_user_id from session
$initiated_by_user_id = $_SESSION['user_id']; // Adjust this according to your session structure

// Sanitize and validate withdrawal data
$accountId = filter_input(INPUT_POST, 'accountId', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_NUMBER_INT);

logMessage("Withdrawal request received - Account ID: $accountId, Amount: $amount, Phone: $phone");

if ($accountId && $amount && $amount > 0 && $phone) {
    // Begin transaction
    $conn->begin_transaction();

    try {
        // Fetch updated_balance from the database
        $balanceStmt = $conn->prepare("SELECT updated_balance, name FROM accounts WHERE id = ?");
        $balanceStmt->bind_param("i", $accountId);
        $balanceStmt->execute();
        $balanceResult = $balanceStmt->get_result();
        $accountData = $balanceResult->fetch_assoc();
        $balanceStmt->close();

        if (!$accountData) {
            throw new Exception('Account not found');
        }

        $updatedBalance = $accountData['updated_balance'];
        $accountName = $accountData['name'];

        logMessage("Account balance retrieved - Current balance: $updatedBalance");

        // Check if sufficient balance exists
        if ($updatedBalance >= $amount) {
            // Update balance
            $newBalance = $updatedBalance - $amount;
            $updateStmt = $conn->prepare("UPDATE accounts SET updated_balance = ? WHERE id = ?");
            $updateStmt->bind_param("di", $newBalance, $accountId);
            $updateStmt->execute();
            $updateStmt->close();

            logMessage("Account balance updated - New balance: $newBalance");

            // Get access token for M-Pesa B2C
            $accessToken = getAccessToken($consumer_key, $consumer_secret);
            if ($accessToken === null) {
                throw new Exception('Failed to get access token');
            }

            // Initiate B2C transaction
            $b2cResponse = initiateB2CTransaction($accessToken, $initiator_name, $security_credential, $command_id, $amount, $party_a, $phone, "Withdrawal for account $accountName", $queue_timeout_url, $result_url);

            if ($b2cResponse === null || !isset($b2cResponse['ConversationID'], $b2cResponse['OriginatorConversationID'])) {
                throw new Exception('Failed to initiate B2C transaction');
            }

            // Insert withdrawal record
            $withdrawalStmt = $conn->prepare("INSERT INTO withdrawals (originator_conversation_id, conversation_id, transaction_amount, receiver_party_public_name, status, initiated_by_user_id, account_debited) VALUES (?, ?, ?, ?, 'PENDING', ?, ?)");
            $withdrawalStmt->bind_param("ssdssi", 
                $b2cResponse['OriginatorConversationID'],$b2cResponse['ConversationID'],
                $amount,
                $phone,
                $initiated_by_user_id,
                $accountId
            );

            if (!$withdrawalStmt->execute()) {
                logMessage("Error executing withdrawal statement: " . $conn->error);
                throw new Exception("Failed to log withdrawal in database.");
            }
            $withdrawalId = $withdrawalStmt->insert_id;
            $withdrawalStmt->close();

            // Insert withdrawal record into expenses table
            $expenseStmt = $conn->prepare("INSERT INTO expenses (expense_name, description, account_name, amount) VALUES (?, ?, ?, ?)");
            $expenseDescription = "Withdrawal processed";
            $expenseStmt->bind_param("sssd", $accountName, $expenseDescription, $accountName, $amount);
            $expenseStmt->execute();
            $expenseStmt->close();

            logMessage("Expense record inserted");

            // Commit transaction
            $conn->commit();

            // Send notifications after successful database insertion
            sendNotifications($conn, $withdrawalId);

            logMessage("Withdrawal process completed successfully");

            echo json_encode(['success' => true, 'message' => 'Withdrawal initiated successfully']);
        } else {
            throw new Exception('Insufficient balance');
        }
    } catch (Exception $e) {
        // Rollback transaction if any error occurs
        $conn->rollback();
        logMessage("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    logMessage("Invalid input data received");
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
}
?>