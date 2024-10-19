<?php
require 'db.php'; // Include your database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
    $currentYearOfStudy = isset($_POST['current_year_of_study']) ? intval($_POST['current_year_of_study']) : 0;
    $manifestJewel = isset($_POST['manifest_jewel']) ? trim($_POST['manifest_jewel']) : '';

    // Validate form data
    if (empty($name) || empty($whatsapp) || $currentYearOfStudy <= 0 || !in_array($manifestJewel, ['Manifest', 'Jewel'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid form data']);
        exit();
    }

    // Insert into database
    $sql = "INSERT INTO members (name, whatsapp, current_year_of_study, manifest_jewel, status) VALUES (?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssis", $name, $whatsapp, $currentYearOfStudy, $manifestJewel);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add member']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
    $conn->close();
}
?>
