<?php
require 'db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$group = $_POST['group'] ?? 'mission_users'; // Default to 'mission_users' if no group is selected

// Fetch users for the message dropdown based on the selected group
if ($group === 'members') {
    $query = "SELECT id, name, whatsapp FROM members";
} elseif ($group === 'associates') {
    $query = "SELECT id, name, phone AS whatsapp FROM associates";
} else {
    // Default to mission_users
    $query = "SELECT id, name, whatsapp FROM users";
}
$result = $conn->query($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = $_POST['message'];
    $user_ids = $_POST['user_ids'] ?? [];
    $send_to_all = isset($_POST['send_to_all']);

    if ($send_to_all) {
        $user_ids = []; // Empty user_ids if sending to all
    }

    // Send message to selected users or all users
    $api_key = '5fj0vi3rjpk6s89b'; // Replace with your actual API key
    $api_url = 'https://api.ultramsg.com/instance93191/messages/chat'; // Replace with your API URL

    if ($send_to_all) {
        if ($group === 'members') {
            $query = "SELECT whatsapp, name FROM members";
        } elseif ($group === 'associates') {
            $query = "SELECT phone AS whatsapp, name FROM associates";
        } else {
            $query = "SELECT whatsapp, name FROM users";
        }
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $user_ids[] = ['whatsapp' => $row['whatsapp'], 'name' => $row['name']];
        }
    } else {
        $user_ids = array_map(function($id) use ($conn, $group) {
            if ($group === 'members') {
                $query = "SELECT whatsapp, name FROM members WHERE whatsapp = '$id'";
            } elseif ($group === 'associates') {
                $query = "SELECT phone AS whatsapp, name FROM associates WHERE phone = '$id'";
            } else {
                $query = "SELECT whatsapp, name FROM users WHERE whatsapp = '$id'";
            }
            $result = $conn->query($query);
            return $result->fetch_assoc();
        }, $user_ids);
    }

    foreach ($user_ids as $user) {
        $personalized_message = str_replace('{{name}}', $user['name'], $message);
        $postData = [
            'token' => $api_key,
            'to' => $user['whatsapp'],
            'body' => $personalized_message,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Handle the response if necessary
    }

    $success_message = "Message sent successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mout Jkuat Admin Communication Module!</title>
    <link rel="stylesheet" href="message.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Messaging Module!</h1>
        </div>
        <?php if (isset($success_message)): ?>
            <div class="notification">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <form action="index" method="post" class="message-form">
            <div class="form-group">
                <label for="group">Select Group:</label>
                <select id="group" name="group" onchange="this.form.submit()">
                    <option value="mission_users" <?php if ($group === 'mission_users') echo 'selected'; ?>>Mission Users</option>
                    <option value="members" <?php if ($group === 'members') echo 'selected'; ?>>Members</option>
                    <option value="associates" <?php if ($group === 'associates') echo 'selected'; ?>>Associates</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="4" required placeholder="Hello {{name}}, ..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="user_ids">Select Users:</label>
                <select id="user_ids" name="user_ids[]" multiple>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['whatsapp']); ?>">
                            <?php echo htmlspecialchars($row['name']); ?> (<?php echo htmlspecialchars($row['whatsapp']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="send_to_all" value="1"> Send to all users
                </label>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send</button>
        </form>
        <a href="../index" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    <script>// script.js (optional for additional animations and interactions)
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional JavaScript interactions or animations here
});
</script>
</body>
</html>
