<?php
session_start();
require('fpdf/fpdf.php'); // Make sure to include the FPDF library

// Database connection
$conn = mysqli_connect("localhost", "moutjkua_admin", "Elijah@10519", "moutjkua_mission");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

// Get the family ID from URL parameter
$family_id = isset($_GET['family_id']) ? (int)$_GET['family_id'] : 0;

// Fetch family details
$family_query = "SELECT * FROM families WHERE id = $family_id";
$family_result = mysqli_query($conn, $family_query);
$family = mysqli_fetch_assoc($family_result);

// Handle search
$search_term = isset($_POST['search_term']) ? mysqli_real_escape_string($conn, $_POST['search_term']) : '';
$search_query = "SELECT m.*, fm.id AS family_member_id
                  FROM members m
                  JOIN family_members fm ON m.id = fm.member_id
                  WHERE fm.family_id = $family_id";

if ($search_term) {
    $search_query .= " AND (m.name LIKE '%$search_term%' OR m.whatsapp LIKE '%$search_term%')";
}

$search_result = mysqli_query($conn, $search_query);

// Handle delete member
if (isset($_POST['delete_member'])) {
    $family_member_id = (int)$_POST['family_member_id'];
    $delete_query = "DELETE FROM family_members WHERE id = $family_member_id";
    mysqli_query($conn, $delete_query);
    header("Location: family_page.php?family_id=$family_id");
    exit;
}

// Handle edit member
if (isset($_POST['edit_member'])) {
    $family_member_id = (int)$_POST['family_member_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $edit_query = "UPDATE members
                   JOIN family_members ON members.id = family_members.member_id
                   SET members.status = '$status'
                   WHERE family_members.id = $family_member_id";
    mysqli_query($conn, $edit_query);
    header("Location: family_page.php?family_id=$family_id");
    exit;
}

// Handle PDF export
if (isset($_GET['export_pdf'])) {
    // Create PDF
    class PDF extends FPDF
    {
        function Header()
        {
            global $family;
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, $family['name'] . ' - Members List', 0, 1, 'C');
            $this->Ln(10);
        }

        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);

    // Add table headers
    $pdf->Cell(60, 10, 'Name', 1);
    $pdf->Cell(40, 10, 'WhatsApp', 1);
    $pdf->Cell(50, 10, 'Current Year of Study', 1);
    $pdf->Cell(40, 10, 'Status', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 12);

    // Add table rows
    mysqli_data_seek($search_result, 0); // Reset the result pointer
    while ($member = mysqli_fetch_assoc($search_result)) {
        $pdf->Cell(60, 10, $member['name'], 1);
        $pdf->Cell(40, 10, $member['whatsapp'], 1);
        $pdf->Cell(50, 10, $member['current_year_of_study'], 1);
        $pdf->Cell(40, 10, $member['status'], 1);
        $pdf->Ln();
    }

    // Output PDF
    $pdf->Output('D', $family['name'] . '_members.pdf');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($family['name']); ?> - Members</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
<div class="wrapper">
      <!-- Include the sidebar -->
      <?php include 'sidebar.php'; ?>

      <!-- Main Panel -->
      <div class="main-panel">
      <?php include 'header.php'; ?>
      <div class="container">
        <div class="page-inner">
          <div class="container mt-4">
    <h1 class="mb-4"><?php echo htmlspecialchars($family['name']); ?> - Members</h1>

    <!-- Export to PDF button -->
    <a href="?family_id=<?php echo $family_id; ?>&export_pdf=1" class="btn btn-success mb-3">Export to PDF</a>

    <!-- Search Form -->
    <form method="POST" class="mb-4">
        <input type="text" name="search_term" class="form-control" placeholder="Search Members" value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" class="btn btn-primary mt-2">Search</button>
    </form>

    <!-- Members Table -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>WhatsApp</th>
                    <th>Current Year of Study</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($search_result) > 0) {
                    while ($member = mysqli_fetch_assoc($search_result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['whatsapp']); ?></td>
                            <td><?php echo htmlspecialchars($member['current_year_of_study']); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="family_member_id" value="<?php echo $member['family_member_id']; ?>">
                                    <input type="text" name="status" value="<?php echo htmlspecialchars($member['status']); ?>" class="form-control">
                                    <button type="submit" name="edit_member" class="btn btn-warning mt-2">Edit</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="family_member_id" value="<?php echo $member['family_member_id']; ?>">
                                    <button type="submit" name="delete_member" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="5">No members found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>