<?php
$servername = "localhost";
$username = "moutjkua_admin"; // Your DB username
$password = "Elijah@10519"; // Your DB password
$dbname = "moutjkua_mission";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>