<?php
require 'db.php';

$group = $_GET['group'] ?? 'mission_users';

if ($group === 'members') {
    $query = "SELECT id, name, whatsapp FROM members";
} elseif ($group === 'associates') {
    $query = "SELECT id, name, phone AS whatsapp FROM associates";
} else {
    $query = "SELECT id, name, whatsapp FROM users";
}

$result = $conn->query($query);
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = [
        'whatsapp' => $row['whatsapp'],
        'name' => $row['name']
    ];
}

header('Content-Type: application/json');
echo json_encode($users);
