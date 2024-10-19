<?php
require 'db.php'; // Include the database connection file

// Check if an ID was passed in the URL query string
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prepare the SQL statement to fetch the associate's details
    $stmt = $conn->prepare("SELECT id, name, phone, year_joined, year_left FROM associates WHERE id = ?");
    $stmt->bind_param("i", $id);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if an associate was found
    if ($result->num_rows > 0) {
        // Fetch the associate's data as an associative array
        $associate = $result->fetch_assoc();

        // Send the data as a JSON response
        echo json_encode($associate);
    } else {
        // No associate found with the given ID
        echo json_encode(['error' => 'Associate not found']);
    }

    // Close the statement
    $stmt->close();
} else {
    // No ID was provided in the query string
    echo json_encode(['error' => 'No ID provided']);
}

// Close the database connection
$conn->close();
?>
