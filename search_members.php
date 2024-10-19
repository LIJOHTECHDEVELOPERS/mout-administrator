<?php
// Database connection
$conn = mysqli_connect("localhost", "moutjkua_admin", "Elijah@10519", "moutjkua_mission");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['search_term'])) {
    $search_term = mysqli_real_escape_string($conn, $_POST['search_term']);
    $search_query = "SELECT * FROM members 
                     WHERE name LIKE '%$search_term%' OR whatsapp LIKE '%$search_term%'";
    $search_result = mysqli_query($conn, $search_query);

    if (mysqli_num_rows($search_result) > 0) {
        echo '<div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>WhatsApp</th>
                            <th>Current Year of Study</th>
                            <th>Status</th>
                            <th>Assign to Family</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while ($member = mysqli_fetch_assoc($search_result)) {
            echo '<tr>
                    <td>' . $member['name'] . '</td>
                    <td>' . $member['whatsapp'] . '</td>
                    <td>' . $member['current_year_of_study'] . '</td>
                    <td>' . $member['status'] . '</td>
                    <td>
                        <form class="assignForm">
                            <input type="hidden" name="member_id" value="' . $member['id'] . '">
                            <select name="family_id" class="form-select" required>
                                <option value="" disabled selected>Select Family</option>';
            
            $family_query = "SELECT * FROM families";
            $family_result = mysqli_query($conn, $family_query);
            while ($family = mysqli_fetch_assoc($family_result)) {
                echo '<option value="' . $family['id'] . '">' . $family['name'] . '</option>';
            }
            
            echo '</select>
                    <button type="submit" name="assign_member" class="btn btn-success mt-2">Assign</button>
                </form>
            </td>
        </tr>';
        }
        
        echo '</tbody></table></div>';
    } else {
        echo '<p>No members found.</p>';
    }
}
?>