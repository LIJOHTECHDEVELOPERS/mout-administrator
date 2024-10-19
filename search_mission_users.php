<?php
require 'db.php';

$search_query = "";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $search_query = "WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
}
$query = "SELECT name, role, whatsapp, email FROM users $search_query ORDER BY id DESC";
$result = $conn->query($query);
?>

<table id="users-table">
    <thead>
        <tr>
            <th>No.</th>
            <th>Name</th>
            <th>Role</th>
            <th>WhatsApp Number</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php
            $counter = 1;
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="No."><?php echo $counter++; ?></td>
                    <td data-label="Name"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td data-label="Role"><?php echo htmlspecialchars($row['role']); ?></td>
                    <td data-label="WhatsApp Number"><?php echo htmlspecialchars($row['whatsapp']); ?></td>
                    <td data-label="Email"><?php echo htmlspecialchars($row['email']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No mission users found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
