<?php
// M-Pesa B2C Integration

// Required M-Pesa API credentials
$consumer_key = '2RxLaz9AVMrdb3vHq9MVLpMxbrKd55IDpDSzG8dGvoj6mQti';
$consumer_secret = 'lZwDUKwKJnjVkGmDb9vBOLjY17X06hYhHEWDmYq5tlOgPAlZF2tzpt4r5swdQKy6';
$initiator_name = 'Alexnjoroge';
$security_credential = 'UNIJQnVkJcztRQf0S3WvDLPuxVhU0d5LYw73KTyeTpksKwXydicxQ3VBJIL90hDn2wdORg8xC3ANgnNJOPWBPFxBOv30208aaDufU5KRkfwvvauJsAbmDuZNkabwdzPBMBGEmbS7pUzFmVDvYrELU2U5j1GWi+/i//Px4BEYyZHLANhsr+ueDOorQcZnT+IC3QDYEbqI7pLcCg4h52kOfkJJ8MR1r/l+8w+L272OX9MyOYCcLlv5Oys0ToOHm1SG1m47J9FawLAfD1UrU0WhknjanIpErdCRHfDQgc+a/ISvXN58EsKZ6gZIycq1fPqzve2wleKjE6O5MwrtHfnoEw==';
$command_id = 'BusinessPayment'; // Or SalaryPayment, or PromotionPayment
$amount = ''; // Amount to be transacted
$party_a = '4131985'; // The organization sending the transaction
$party_b = ''; // The mobile number receiving the transaction
$remarks = 'B2C payment';
$queue_timeout_url = 'https://dash.moutjkuatministry.cloud/timeout_url.php';
$result_url = 'https://dash.moutjkuatministry.cloud/result_url.php';
$occasion = ''; // Optional

// Generate access token
function getAccessToken($consumer_key, $consumer_secret) {
    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result = json_decode($result);
    $access_token = $result->access_token;
    curl_close($curl);
    return $access_token;
}

// Initiate B2C transaction
function initiateB2CTransaction($access_token, $initiator_name, $security_credential, $command_id, $amount, $party_a, $party_b, $remarks, $queue_timeout_url, $result_url, $occasion) {
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
        'Occasion' => $occasion
    );
    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    return $curl_response;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $party_b = $_POST['phone_number'];
    
    $access_token = getAccessToken($consumer_key, $consumer_secret);
    $response = initiateB2CTransaction($access_token, $initiator_name, $security_credential, $command_id, $amount, $party_a, $party_b, $remarks, $queue_timeout_url, $result_url, $occasion);
    
    // Process the response here
    // You might want to store the transaction details in your database
    // and show a success or error message to the user
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa B2C Withdrawal</title>
</head>
<body>
    <h2>M-Pesa B2C Withdrawal Form</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="amount">Amount:</label>
        <input type="number" id="amount" name="amount" required><br><br>
        
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" required><br><br>
        
        <input type="submit" value="Initiate Withdrawal">
    </form>
</body>
</html>