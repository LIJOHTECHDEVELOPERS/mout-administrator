<?php
session_start();
// Database connection
$conn = mysqli_connect("localhost", "moutjkua_admin", "Elijah@10519", "moutjkua_mission");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Load PhpSpreadsheet and other required libraries
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
// Fetch family overview
$overview_query = "SELECT f.id AS family_id, f.name AS family_name, COUNT(fm.id) AS total_members
                   FROM families f
                   LEFT JOIN family_members fm ON f.id = fm.family_id
                   GROUP BY f.id";
$overview_result = $conn->query($overview_query);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['success' => false, 'message' => 'Unknown action'];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'fetch_family_data':
                fetchFamilyData($conn);
                break;

            case 'assign':
                assignMemberToFamily($conn);
                break;

            case 'search':
                    searchMembers($conn);
                    break;
            case 'export':
                exportFamilyData($conn, $_POST['format']);
                break;

            default:
                echo json_encode($response);
                break;
        }
    }
    exit;
}

// Function to fetch family data for charts
function fetchFamilyData($conn) {
    $family_data_query = "SELECT f.name AS family_name, COUNT(fm.id) AS total_members
                          FROM families f
                          LEFT JOIN family_members fm ON f.id = fm.family_id
                          GROUP BY f.id";
    $stmt = $conn->prepare($family_data_query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $families = [];
        $members_count = [];

        while ($row = $result->fetch_assoc()) {
            $families[] = $row['family_name'];
            $members_count[] = $row['total_members'];
        }

        echo json_encode(['success' => true, 'families' => $families, 'members_count' => $members_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No family data found']);
    }
}

// Function to assign members to a family
function assignMemberToFamily($conn) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['family_id']) || !isset($_POST['member_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }

    $family_id = $conn->real_escape_string($_POST['family_id']);
    $member_id = $conn->real_escape_string($_POST['member_id']);

    // Prevent assigning one person to multiple families
    $check_member_query = $conn->prepare("SELECT * FROM family_members WHERE member_id = ?");
    $check_member_query->bind_param("s", $member_id);
    $check_member_query->execute();
    $check_member_result = $check_member_query->get_result();

    if ($check_member_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Member is already assigned to a family']);
    } else {
        $assign_query = $conn->prepare("INSERT INTO family_members (family_id, member_id) VALUES (?, ?)");
        $assign_query->bind_param("ss", $family_id, $member_id);
        $assign_result = $assign_query->execute();

        if ($assign_result) {
            echo json_encode(['success' => true, 'message' => 'Member assigned successfully!', 'redirect' => 'overview']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign member']);
        }
    }
}
function searchMembers($conn) {
    $search_term = '%' . $conn->real_escape_string($_POST['search_term']) . '%';
    
    $search_query = "SELECT id, name, whatsapp FROM members WHERE name LIKE ? OR whatsapp LIKE ?";
    $stmt = $conn->prepare($search_query);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    
    // Fetch families
    $family_query = "SELECT id, name FROM families";
    $family_result = $conn->query($family_query);
    $families = [];
    while ($family = $family_result->fetch_assoc()) {
        $families[] = $family;
    }
    
    if (count($members) > 0) {
        echo json_encode(['success' => true, 'members' => $members, 'families' => $families]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No members found', 'families' => $families]);
    }
}
function exportFamilyData($conn, $format) {
    header('Content-Type: application/json');
    
    $export_query = "SELECT f.id AS family_id, f.name AS family_name, COUNT(fm.id) AS total_members
                     FROM families f
                     LEFT JOIN family_members fm ON f.id = fm.family_id
                     GROUP BY f.id";
    $export_result = $conn->query($export_query);

    if ($export_result->num_rows > 0) {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Add family statistics headers
            $sheet->setCellValue('A1', 'Family ID')
                  ->setCellValue('B1', 'Family Name')
                  ->setCellValue('C1', 'Total Members');

            // Apply styles to header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4CAF50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

            // Set column width
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(20);

            // Set row height
            $sheet->getDefaultRowDimension()->setRowHeight(20);

            // Add family data
            $row_number = 2;
            while ($row = $export_result->fetch_assoc()) {
                $sheet->setCellValue('A' . $row_number, $row['family_id'])
                      ->setCellValue('B' . $row_number, $row['family_name'])
                      ->setCellValue('C' . $row_number, $row['total_members']);
                $sheet->getStyle("A$row_number:C$row_number")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
                ]);
                $row_number++;

                // Fetch family members
                $members_query = "SELECT m.name AS member_name 
                                  FROM family_members fm
                                  JOIN members m ON fm.member_id = m.id
                                  WHERE fm.family_id = ?";
                $stmt = $conn->prepare($members_query);
                $stmt->bind_param("i", $row['family_id']);
                $stmt->execute();
                $members_result = $stmt->get_result();

                if ($members_result->num_rows > 0) {
                    while ($member = $members_result->fetch_assoc()) {
                        $sheet->setCellValue('A' . $row_number, $member['member_name']);
                        $sheet->mergeCells("A$row_number:C$row_number");
                        $sheet->getStyle("A$row_number:C$row_number")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
                        ]);
                        $row_number++;
                    }
                }
            }

            $filename = 'family_data_' . date('Y-m-d_H-i-s');

            if ($format === 'excel') {
                $writer = new Xlsx($spreadsheet);
                $filePath = $filename . '.xlsx';
                $writer->save($filePath);
                echo json_encode(['success' => true, 'message' => 'Excel file created successfully', 'file' => $filePath]);
            } elseif ($format === 'pdf') {
                // PDF generation using PhpSpreadsheet with mPDF
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf($spreadsheet);
                $writer->save($filename . '.pdf');
                echo json_encode(['success' => true, 'message' => 'PDF file created successfully', 'file' => $filename . '.pdf']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid export format']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred during export: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No data available for export']);
    }
}

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css"> <script src="assets/js/plugin/webfont/webfont.min.js"></script>
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
        <h1 class="mb-4">Family Management</h1>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">Overview</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assign-members-tab" data-bs-toggle="tab" data-bs-target="#assign-members" type="button" role="tab" aria-controls="assign-members" aria-selected="false">Assign Members</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="family-chart-tab" data-bs-toggle="tab" data-bs-target="#family-chart" type="button" role="tab" aria-controls="family-chart" aria-selected="false">Family Chart Statistics</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <h2 class="mt-3">Family Overview</h2>
                <div class="row">
                    <?php while ($row = mysqli_fetch_assoc($overview_result)) { ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['family_name']); ?></h5>
                                <p class="card-text">Total Members: <?php echo $row['total_members']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-pane fade" id="assign-members" role="tabpanel" aria-labelledby="assign-members-tab">
                <h2 class="mt-3">Assign Members to Families</h2>
                <form id="searchForm" class="mb-4">
                    <div class="form-group">
                        <label for="search_term">Search Member:</label>
                        <input type="text" class="form-control" id="search_term" name="search_term" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                </form>

                <div id="searchResults"></div>
            </div>

            <div class="tab-pane fade" id="family-chart" role="tabpanel" aria-labelledby="family-chart-tab">
            </div>
        </div>

        <div class="mt-4">
            <button type="button" class="btn btn-primary export-btn" data-format="excel">Export All to Excel</button>
            <button type="button" class="btn btn-secondary export-btn" data-format="pdf">Export All to PDF</button>
        </div>
    </div>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
    <script>
    $(document).ready(function() {
    // Search members and display in the table
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        var searchTerm = $('#search_term').val();
        $.post('family_overview.php', { action: 'search', search_term: searchTerm }, function(response) {
            if (response.success) {
                var resultsHtml = '<table class="table table-bordered"><thead><tr><th>Name</th><th>WhatsApp</th><th>Assign to Family</th></tr></thead><tbody>';
                response.members.forEach(function(member) {
                    resultsHtml += '<tr><td>' + member.name + '</td><td>' + member.whatsapp + '</td><td>';
                    resultsHtml += '<form class="assignForm"><input type="hidden" name="member_id" value="' + member.id + '">';
                    resultsHtml += '<select name="family_id" class="form-control"><option value="">Select Family</option>';
                    
                    // Populate family options
                    response.families.forEach(function(family) {
                        resultsHtml += '<option value="' + family.id + '">' + family.name + '</option>';
                    });
                    
                    resultsHtml += '</select><button type="submit" class="btn btn-success mt-2">Assign</button></form></td></tr>';
                });
                resultsHtml += '</tbody></table>';
                $('#searchResults').html(resultsHtml);
            } else {
                $('#searchResults').html('<p>' + response.message + '</p>');
            }
        }, 'json');
    });

    // Handle member assignment
    $(document).on('submit', '.assignForm', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('family_overview.php', formData + '&action=assign', function(response) {
            if (response.success) {
                iziToast.success({
                    title: 'Success',
                    message: response.message || 'Member assigned successfully!'
                });
                if (response.redirect === 'overview') {
                    $('#overview-tab').trigger('click'); // Switch back to overview tab
                }
            } else {
                iziToast.error({
                    title: 'Error',
                    message: response.message || 'An error occurred while assigning the member'
                });
            }
        }, 'json')
        .fail(function(jqXHR, textStatus, errorThrown) {
            iziToast.error({
                title: 'Error',
                message: 'An error occurred: ' + textStatus
            });
            console.error('Error details:', errorThrown);
        });
    });

    // Handle export
    $('.export-btn').on('click', function() {
        var format = $(this).data('format');
        $.post('family_overview.php', { action: 'export', format: format }, function(response) {
            if (response.success) {
                iziToast.success({
                    title: 'Success',
                    message: response.message || 'Data exported successfully!'
                });
                if (response.file) {
                    window.location.href = response.file;
                }
            } else {
                iziToast.error({
                    title: 'Error',
                    message: response.message || 'An error occurred during export'
                });
            }
        }, 'json')
        .fail(function(jqXHR, textStatus, errorThrown) {
            iziToast.error({
                title: 'Error',
                message: 'An error occurred: ' + textStatus
            });
            console.error('Error details:', errorThrown);
        });
    });

    // Fetch family data for the chart
    $.post('family_overview.php', { action: 'fetch_family_data' }, function(response) {
        if (response.success) {
            // Use the response data to populate the chart
            var ctx = document.getElementById('familyChart').getContext('2d');
            var familyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: response.families, // Dynamic family names
                    datasets: [{
                        label: 'Number of Members',
                        data: response.members_count, // Dynamic member counts
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else {
            iziToast.error({ title: 'Error', message: response.message });
        }
    }, 'json');
});

</script>
<canvas id="familyChart" width="400" height="200"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Fetch family data for the chart
        $.post('family_overview.php', { action: 'fetch_family_data' }, function(response) {
            if (response.success) {
                // Use the response data to populate the chart
                var ctx = document.getElementById('familyChart').getContext('2d');
                var familyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: response.families, // Dynamic family names
                        datasets: [{
                            label: 'Number of Members',
                            data: response.members_count, // Dynamic member counts
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                iziToast.error({ title: 'Error', message: response.message });
            }
        }, 'json');
    });
</script>


</body>
</html>