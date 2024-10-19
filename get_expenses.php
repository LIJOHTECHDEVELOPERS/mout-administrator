<?php
$conn = new mysqli('localhost', 'root', '', 'leadersportal');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT amount, created_at FROM expenses";
$result = $conn->query($sql);

$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

echo json_encode($expenses);
$conn->close();
?>
