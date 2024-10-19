<?php
require 'db.php';

$query = "SELECT name, phone, year_joined, year_left FROM associates ORDER BY id DESC";
$result = $conn->query($query);

$associates = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $associates[] = $row;
    }
}

echo json_encode($associates);
?>
