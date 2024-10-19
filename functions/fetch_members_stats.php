<?php
// Database connection
include 'db.php'; // Ensure your connection details are correct

$query = "
    SELECT 
        current_year_of_study AS year, 
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_members, 
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_members
    FROM members
    GROUP BY current_year_of_study
    ORDER BY year ASC;
";

$result = mysqli_query($conn, $query);

$years = [];
$activeMembers = [];
$inactiveMembers = [];

while ($row = mysqli_fetch_assoc($result)) {
    $years[] = $row['year'];
    $activeMembers[] = $row['active_members'];
    $inactiveMembers[] = $row['inactive_members'];
}

echo json_encode([
    'years' => $years,
    'activeMembers' => $activeMembers,
    'inactiveMembers' => $inactiveMembers
]);

mysqli_close($conn);
?>
