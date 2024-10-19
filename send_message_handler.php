<?php
require 'db.php';
session_start();

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You must be logged in to send messages.']);
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
    $query = "SELECT id, name, whatsapp FROM mission_users";
}
$result = $conn->query($query);

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message'])) {
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
                $query = "SELECT whatsapp, name FROM mission_users";
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
                    $query = "SELECT whatsapp, name FROM mission_users WHERE whatsapp = '$id'";
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
        }

        echo json_encode(['success' => 'Message sent successfully!']);
        exit();
    }
}

// Return user list for AJAX requests
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'whatsapp' => $row['whatsapp']
    ];
}

echo json_encode(['users' => $users]);
?>
