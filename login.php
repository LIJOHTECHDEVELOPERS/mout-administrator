<?php 
require 'db.php';
session_start();

// Enable output buffering to prevent header errors
ob_start();

ini_set('display_errors', 1); // Disable this in production
error_reporting(E_ALL);

define('DEBUG_MODE', true); // Set this to false in production

$error_message = "";
$debug_message = "";
$show_otp_form = false;
$user = null; // Initialize the $user variable to avoid undefined variable error

// Function to generate a 6-digit OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send WhatsApp message
function sendWhatsAppMessage($number, $message) {
    global $debug_message;

    $api_url = 'http://34.41.242.25:3000/client/sendMessage/8b29c146-ab7e-4104-9973-5da3bd9bcf5d'; // Replace with actual API URL

    $postData = [
        'chatId' => $number . '@c.us',
        'contentType' => 'string',
        'content' => $message
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

    $debug_message .= "WhatsApp API Response: HTTP Code: $http_code, Response: $response\n";

    if ($http_code == 200) {
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'];
    }

    return false;
}

// Function to send OTP via email
function sendOTPviaEmail($email, $otp) {
    global $debug_message;

    $subject = "Your Login OTP";
    $message = "Your OTP for login is: $otp";
    $headers = "From: hi@moutjkuatministry.cloud\r\n";

    $result = mail($email, $subject, $message, $headers);
    $debug_message .= "Email send result: " . ($result ? "Success" : "Failure") . "\n";

    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Check if user exists in the database
        $sql = "SELECT * FROM admin_users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify password
                if (password_verify($password, $user['password'])) {
                    $otp = generateOTP();
                    $_SESSION['login_otp'] = $otp;
                    $_SESSION['login_user_id'] = $user['id'];
                    $_SESSION['otp_time'] = time(); // Store OTP generation time

                    // Send OTP via WhatsApp
                    $whatsapp_success = sendWhatsAppMessage($user['whatsapp'], "Your OTP for login is: $otp");
                    // Send OTP via Email
                    $email_success = sendOTPviaEmail($user['email'], $otp);

                    // Check if both WhatsApp and email were successful
                    if ($whatsapp_success || $email_success) {
                        $_SESSION['otp_method'] = ($whatsapp_success ? 'WhatsApp' : '') . ($email_success ? ' and Email' : '');
                        $show_otp_form = true;
                    } else {
                        $error_message = "Failed to send OTP via both WhatsApp and email. Please try again or contact support.";
                    }
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No user found with that email address.";
            }

            $stmt->close();
        } else {
            $error_message = "Failed to prepare the SQL statement.";
        }
    } elseif (isset($_POST['otp'])) {
        // Check if OTP is valid and not expired (valid for 5 minutes)
        $otp_age = time() - $_SESSION['otp_time'];
        if ($_POST['otp'] === $_SESSION['login_otp'] && $otp_age <= 300) {
            $user_id = $_SESSION['login_user_id'];
            $sql = "SELECT * FROM admin_users WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                // Set session variables to log in the user
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_avatar'] = 'assets/img/profile.jpg';

                // Clear OTP session data
                unset($_SESSION['login_otp']);
                unset($_SESSION['login_user_id']);
                unset($_SESSION['otp_method']);

                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Failed to complete login. Please try again.";
            }
        } else {
            $error_message = "Invalid or expired OTP. Please try again.";
            $show_otp_form = true;
        }
    }
}

$conn->close();
ob_end_clean(); // Clear buffer before redirecting if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Login with 2FA</title>
  <link href="https://fonts.googleapis.com/css?family=Karla:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.materialdesignicons.com/4.8.95/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="auth/css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
</head>
<body>
  <main>
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6 login-section-wrapper">
          <div class="brand-wrapper">
            <img src="assets/img/moutlogo.png" alt="logo" class="logo">
          </div>
          <div class="login-wrapper my-auto">
            <h1 class="login-title"><?php echo $show_otp_form ? 'Enter OTP' : 'Log in'; ?></h1>
            <?php if (!$show_otp_form) { ?>
              <form method="post">
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" name="email" id="email" class="form-control" placeholder="email@example.com" required>
                </div>
                <div class="form-group mb-4">
                  <label for="password">Password</label>
                  <input type="password" name="password" id="password" class="form-control" placeholder="enter your password" required>
                </div>
                <input name="submit" id="submit" class="btn btn-block login-btn" type="submit" value="Login">
              </form>
            <?php } else { ?>
              <form method="post">
                <div class="form-group">
                  <label for="otp">Enter OTP sent to your <?php echo $_SESSION['otp_method']; ?></label>
                  <input type="text" name="otp" id="otp" class="form-control" placeholder="Enter 6-digit OTP" required>
                </div>
                <input name="submit_otp" id="submit_otp" class="btn btn-block login-btn" type="submit" value="Verify OTP">
              </form>
            <?php } ?>
          </div>
        </div>
        <div class="col-sm-6 px-0 d-none d-sm-block">
          <img src="auth/img/login.jpg" alt="login image" class="login-img">
        </div>
      </div>
    </div>
  </main>
<!-- iziToast JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
  <?php if (!empty($error_message)) { ?>
    <script>
      iziToast.error({
        title: 'Error',
        message: '<?php echo $error_message; ?>',
        position: 'topRight'
      });
    </script>
  <?php } ?>

  <?php if (DEBUG_MODE && !empty($debug_message)) { ?>
    <script>
      console.log(<?php echo json_encode($debug_message); ?>);
    </script>
  <?php } ?>
  
</body>
</html>
