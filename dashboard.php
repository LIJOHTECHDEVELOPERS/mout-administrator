<?php
require 'db.php';
// Fetch the count of registered users
$sql_count = "SELECT COUNT(*) AS user_count FROM users";
$result_count = $conn->query($sql_count);

// Fetch the count result
$user_count = 0;
if ($result_count->num_rows > 0) {
    $row = $result_count->fetch_assoc();
    $user_count = $row["user_count"];
}
$sql = "SELECT COUNT(*) AS total_users FROM associates";
$result = $conn->query($sql);

// Fetch the result
$total_users = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_users = $row['total_users'];
} else {
    echo "0 results";
}
// Fetch the count of members
$sql = "SELECT COUNT(*) AS total_count FROM members";
$result = $conn->query($sql);
$total_count = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_count = $row['total_count'];
} else {
    echo "0 results";
}
?>
<section>
<!-- MAIN -->
        <main id="main-content">
            <div id="dashboard-content">
                <div class="head-title">
                    <div class="left">
                        <h1>Dashboard</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="#">Dashboard</a>
                            </li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a class="active" href="#">Home</a>
                            </li>
                        </ul>
                    </div>
                    <a href="#" class="btn-download" id="export-pdf">
                        <i class='bx bxs-cloud-download'></i>
                        <span class="text">Export to PDF</span>
                    </a>
                    <a href="#" class="btn-download" id="export-xls">
                        <i class='bx bxs-cloud-download'></i>
                        <span class="text">Export to XLS</span>
                    </a>
                </div>

               <ul class="box-info">
                    <li>
                        <i class='bx bxs-group'></i>
                        <span class="text">
                            <h3><?php echo $total_count; ?></h3>
                            <p>Active Members</p>
                        </span>
                    </li>
                    <li>
                        <i class='bx bxs-group'></i>
                        <span class="text">
                            <h3><?php echo $user_count; ?></h3>
                            <p>Mission Registrations</p>
                        </span>
                    </li>
                    <li>
                        <i class='bx bxs-user-detail'></i>
                        <span class="text">
                            <h3><?php echo $total_users; ?></h3>
                            <p>Associates</p>
                        </span>
                    </li>
                </ul>
            </div>

            <div id="mission-users-content" class="hidden">
                <div class="head-title">
                    <div class="left">
                        <!--<h4>Mission Registrations August Mission 2024</h4>-->
                    </div>
                </div>

                <div class="table-data">
                    <div class="users">
                        <div class="head">
                            <h5>Mission Registration List</h5>
                        </div>
                        <!--<form class="search-form">
                            <input type="search" id="search-input-users" placeholder="Search...">
                            <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                        </form> -->
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
                                <?php
                                $query = "SELECT name, role, whatsapp, email FROM users ORDER BY id DESC";
                                $result = $conn->query($query);
                                if ($result->num_rows > 0):
                                    $counter = 1; // Initialize counter
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
                    </div>
                </div>
            </div>
        </main>
        </section>
        <!-- MAIN -->
    