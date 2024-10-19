<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'leadersportal');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to get the total expenses
$sql = "SELECT SUM(amount) as total_expenses FROM expenses";
$result = $conn->query($sql);

$total_expenses = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_expenses = $row['total_expenses'];
}

echo json_encode(['total_expenses' => $total_expenses]);
$conn->close();
?>
