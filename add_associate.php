<?php
require 'db.php';
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['name']) && isset($data['phone']) && isset($data['year_joined'])) {
    $name = $conn->real_escape_string($data['name']);
    $phone = $conn->real_escape_string($data['phone']);
    $year_joined = $conn->real_escape_string($data['year_joined']);
    $year_left = isset($data['year_left']) ? $conn->real_escape_string($data['year_left']) : null;

    $query = "INSERT INTO associates (name, phone, year_joined, year_left) VALUES ('$name', '$phone', '$year_joined', '$year_left')";

    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
