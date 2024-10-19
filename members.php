<?php
require 'db.php';
require 'fpdf/fpdf.php';
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login'); // Redirect to login page
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_status':
                updateMemberStatus($conn);
                break;
            case 'edit_member':
                editMember($conn);
                break;
            case 'delete_member':
                deleteMember($conn);
                break;
            case 'filter_members':
                filterMembers($conn);
                break;
            case 'export_pdf':
                exportPDF($conn);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit();
    }
}


// Fetch all members
$sql = "SELECT id, name, whatsapp, current_year_of_study, status, manifest_jewel FROM members";
$result = $conn->query($sql);

// Function to update member status
function updateMemberStatus($conn) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($id > 0 && in_array($new_status, ['active', 'inactive'])) {
        $sql = "UPDATE members SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $id);
            $stmt->execute();
            echo json_encode(['success' => $stmt->affected_rows > 0]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid status or ID']);
    }
}

// Function to edit member details
function editMember($conn) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
    $year_of_study = isset($_POST['year_of_study']) ? intval($_POST['year_of_study']) : 0;
    $manifest_jewel = isset($_POST['manifest_jewel']) ? trim($_POST['manifest_jewel']) : '';

    if ($id > 0 && !empty($name) && !empty($whatsapp) && $year_of_study > 0 && in_array($manifest_jewel, ['Manifest', 'Jewel'])) {
        $sql = "UPDATE members SET name = ?, whatsapp = ?, current_year_of_study = ?, manifest_jewel = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssisi", $name, $whatsapp, $year_of_study, $manifest_jewel, $id);
            $stmt->execute();
            echo json_encode(['success' => $stmt->affected_rows > 0]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    }
}

// Function to delete a member
function deleteMember($conn) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id > 0) {
        $sql = "DELETE FROM members WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => $stmt->affected_rows > 0]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
    }
}
// New function to filter members
function filterMembers($conn) {
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $manifest_jewel = isset($_POST['manifest_jewel']) ? $_POST['manifest_jewel'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    $sql = "SELECT id, name, whatsapp, current_year_of_study, status, manifest_jewel FROM members WHERE 1=1";
    $params = array();
    $types = "";

    if ($year > 0) {
        $sql .= " AND current_year_of_study = ?";
        $params[] = $year;
        $types .= "i";
    }

    if (!empty($manifest_jewel)) {
        $sql .= " AND manifest_jewel = ?";
        $params[] = $manifest_jewel;
        $types .= "s";
    }

    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $members = array();
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        echo json_encode(['success' => true, 'members' => $members]);
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL preparation error']);
    }
}
function exportPDF($conn) {
    try {
        error_log("Starting PDF export");
        
        $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
        $manifest_jewel = isset($_POST['manifest_jewel']) ? $_POST['manifest_jewel'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';

        error_log("Filters - Year: $year, Manifest/Jewel: $manifest_jewel, Status: $status");

        $sql = "SELECT name, whatsapp, current_year_of_study, status, manifest_jewel FROM members WHERE 1=1";
        $params = array();
        $types = "";

        if ($year > 0) {
            $sql .= " AND current_year_of_study = ?";
            $params[] = $year;
            $types .= "i";
        }

        if (!empty($manifest_jewel)) {
            $sql .= " AND manifest_jewel = ?";
            $params[] = $manifest_jewel;
            $types .= "s";
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }

        error_log("SQL Query: $sql");

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            error_log("Query executed successfully");

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Members List', 0, 1, 'C');
            $pdf->Ln(10);

            // Headers with added numbering column
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(10, 10, '#', 1);  // Number column
            $pdf->Cell(50, 10, 'Name', 1);
            $pdf->Cell(40, 10, 'WhatsApp', 1);
            $pdf->Cell(30, 10, 'Year', 1);
            $pdf->Cell(30, 10, 'Status', 1);
            $pdf->Cell(40, 10, 'Manifest/Jewel', 1);
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 12);
            $rowCount = 1;  // Start the row count at 1
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(10, 10, $rowCount, 1);  // Add row number
                $pdf->Cell(50, 10, $row['name'], 1);
                $pdf->Cell(40, 10, $row['whatsapp'], 1);
                $pdf->Cell(30, 10, $row['current_year_of_study'], 1);
                $pdf->Cell(30, 10, $row['status'], 1);
                $pdf->Cell(40, 10, $row['manifest_jewel'], 1);
                $pdf->Ln();
                $rowCount++;  // Increment the row number
            }

            error_log("Added $rowCount rows to PDF");

            // Clear any output that might have been sent before
            if (ob_get_length()) ob_clean();

            // Set the appropriate headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="members_list.pdf"');

            error_log("Headers set, about to output PDF");

            // Output the PDF
            $pdf->Output('F', 'php://output');
            
            error_log("PDF output complete");
            
            exit();
        } else {
            throw new Exception('SQL preparation error');
        }
    } catch (Exception $e) {
        error_log('PDF Export Error: ' . $e->getMessage());
        
        // Send an error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()]);
        exit();
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
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
    <style>
        .member-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .member-card h5 {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .member-card p {
            margin-bottom: 5px;
        }
        .card-actions {
            display: flex;
            justify-content: flex-end;
        }
        .card-actions button {
            margin-left: 10px;
        }
    </style>
</head>

<body>
<div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <?php include 'header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <button class="btn btn-primary mb-3" onclick="window.location.href='add_member.php';">Add Member</button>
                    <button class="btn btn-success mb-3" id="exportPDF">Export to PDF</button>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="yearFilter" class="form-control">
                                <option value="">All Years</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="manifestJewelFilter" class="form-control">
                                <option value="">All</option>
                                <option value="Manifest">Manifest</option>
                                <option value="Jewel">Jewel</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="search" placeholder="Search for members...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <div id="membersContainer">
                            <?php
                            $i = 1;
                            while ($row = $result->fetch_assoc()): ?>
                                <div class="member-card" data-id="<?= $row['id'] ?>">
                                    <h5><?= $i . ". " . htmlspecialchars($row['name']) ?></h5>
                                    <p><strong>Whatsapp:</strong> <?= htmlspecialchars($row['whatsapp']) ?></p>
                                    <p><strong>Current Year of Study:</strong> <?= htmlspecialchars($row['current_year_of_study']) ?></p>
                                    <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
                                    <p><strong>Manifest/Jewel:</strong> <?= htmlspecialchars($row['manifest_jewel']) ?></p>
                                    <div class="card-actions">
                                        <button class="btn btn-sm btn-secondary btn-status">Update Status</button>
                                        <button class="btn btn-sm btn-warning btn-edit">Edit</button>
                                        <button class="btn btn-sm btn-danger btn-delete">Delete</button>
                                    </div>
                                </div>
                            <?php
                            $i++;
                            endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
    <script>
  $(document).ready(function () {
    function applyFilters() {
        var year = $('#yearFilter').val();
        var manifestJewel = $('#manifestJewelFilter').val();
        var status = $('#statusFilter').val();
        var searchValue = $('#search').val().toLowerCase();

        $.ajax({
            url: 'members.php',
            method: 'POST',
            data: {
                action: 'filter_members',
                year: year,
                manifest_jewel: manifestJewel,
                status: status
            },
            success: function (response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#membersContainer').empty();
                        var i = 1;
                        data.members.forEach(function(member) {
                            if (member.name.toLowerCase().indexOf(searchValue) > -1 ||
                                member.whatsapp.toLowerCase().indexOf(searchValue) > -1) {
                                var card = `
                                    <div class="member-card" data-id="${member.id}">
                                        <h5>${i}. ${member.name}</h5>
                                        <p><strong>Whatsapp:</strong> ${member.whatsapp}</p>
                                        <p><strong>Current Year of Study:</strong> ${member.current_year_of_study}</p>
                                        <p><strong>Status:</strong> ${member.status}</p>
                                        <p><strong>Manifest/Jewel:</strong> ${member.manifest_jewel}</p>
                                        <div class="card-actions">
                                            <button class="btn btn-sm btn-secondary btn-status">Update Status</button>
                                            <button class="btn btn-sm btn-warning btn-edit">Edit</button>
                                            <button class="btn btn-sm btn-danger btn-delete">Delete</button>
                                        </div>
                                    </div>
                                `;
                                $('#membersContainer').append(card);
                                i++;
                            }
                        });
                    } else {
                        Swal.fire('Error', 'Failed to filter members: ' + data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Invalid response from server', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'An error occurred while filtering members', 'error');
            }
        });
    }

    // Apply filters on change
    $('#yearFilter, #manifestJewelFilter, #statusFilter').change(applyFilters);

    // Handle search
    $('#search').on('input', applyFilters);

    // Handle Edit button click
    $(document).on('click', '.btn-edit', function () {
        var card = $(this).closest('.member-card');
        var id = card.data('id');
        var name = card.find('h5').text().split(". ")[1];
        var whatsapp = card.find('p').eq(0).text().replace('Whatsapp: ', '');
        var year = card.find('p').eq(1).text().replace('Current Year of Study: ', '');
        var manifest_jewel = card.find('p').eq(3).text().replace('Manifest/Jewel: ', '');

        Swal.fire({
            title: 'Edit Member',
            html:
                `<input type="hidden" id="memberId" value="${id}">
                <label>Name</label><input type="text" id="memberName" class="swal2-input" value="${name}">
                <label>Whatsapp</label><input type="text" id="memberWhatsapp" class="swal2-input" value="${whatsapp}">
                <label>Year of Study</label><input type="number" id="memberYear" class="swal2-input" value="${year}">
                <label>Manifest/Jewel</label>
                <select id="manifestJewel" class="swal2-input">
                    <option value="Manifest" ${manifest_jewel === 'Manifest' ? 'selected' : ''}>Manifest</option>
                    <option value="Jewel" ${manifest_jewel === 'Jewel' ? 'selected' : ''}>Jewel</option>
                </select>`,
            showCancelButton: true,
            confirmButtonText: 'Save',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const memberId = $('#memberId').val();
                const name = $('#memberName').val();
                const whatsapp = $('#memberWhatsapp').val();
                const year = $('#memberYear').val();
                const manifest_jewel = $('#manifestJewel').val();

                return { memberId, name, whatsapp, year, manifest_jewel };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { memberId, name, whatsapp, year, manifest_jewel } = result.value;

                $.ajax({
                    url: 'members.php',
                    method: 'POST',
                    data: {
                        action: 'edit_member',
                        id: memberId,
                        name: name,
                        whatsapp: whatsapp,
                        year_of_study: year,
                        manifest_jewel: manifest_jewel
                    },
                    success: function (response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire('Success', 'Member details updated!', 'success').then(() => {
                                    applyFilters();
                                });
                            } else {
                                Swal.fire('Error', 'Failed to update member details: ' + data.message, 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Invalid response from server', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'An error occurred while updating member details', 'error');
                    }
                });
            }
        });
    });

    // Handle Delete button click
    $(document).on('click', '.btn-delete', function () {
        var card = $(this).closest('.member-card');
        var id = card.data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'You will not be able to recover this member!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'members.php',
                    method: 'POST',
                    data: { action: 'delete_member', id: id },
                    success: function (response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire('Deleted!', 'Member has been deleted.', 'success').then(() => {
                                    applyFilters();
                                });
                            } else {
                                Swal.fire('Error', 'Failed to delete member: ' + data.message, 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Invalid response from server', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'An error occurred while deleting member', 'error');
                    }
                });
            }
        });
    });

    // Handle Update Status button click
    $(document).on('click', '.btn-status', function () {
        var card = $(this).closest('.member-card');
        var id = card.data('id');
        var currentStatus = card.find('p').eq(2).text().replace('Status: ', '');
        var newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        Swal.fire({
            title: 'Update Status',
            text: `Are you sure you want to change the status to ${newStatus}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'members.php',
                    method: 'POST',
                    data: { action: 'update_status', id: id, status: newStatus },
                    success: function (response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire('Success', 'Member status updated!', 'success').then(() => {
                                    applyFilters();
                                });
                            } else {
                                Swal.fire('Error', 'Failed to update member status: ' + data.message, 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Invalid response from server', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'An error occurred while updating member status', 'error');
                    }
                });
            }
        });
    });

   // Handle Export to PDF button click
$('#exportPDF').click(function() {
    var year = $('#yearFilter').val();
    var manifestJewel = $('#manifestJewelFilter').val();
    var status = $('#statusFilter').val();

    $.ajax({
        url: 'members.php',
        method: 'POST',
        data: {
            action: 'export_pdf',
            year: year,
            manifest_jewel: manifestJewel,
            status: status
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(response, status, xhr) {
            var contentType = xhr.getResponseHeader('content-type');
            console.log('Response Content-Type:', contentType);
            console.log('Response size:', response.size);

            if (contentType === 'application/pdf') {
                // It's a PDF, proceed with download
                var blob = new Blob([response], {type: 'application/pdf'});
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'members_list.pdf';
                link.click();
                
                // Clean up the URL object after the download starts
                setTimeout(function() {
                    window.URL.revokeObjectURL(link.href);
                }, 100);
            } else {
                // It's not a PDF, try to read it as text
                var reader = new FileReader();
                reader.onload = function() {
                    console.log('Response text:', reader.result);
                    try {
                        var jsonResponse = JSON.parse(reader.result);
                        Swal.fire('Error', 'Server response: ' + (jsonResponse.message || 'Unknown error'), 'error');
                    } catch (e) {
                        Swal.fire('Error', 'Server response: ' + reader.result, 'error');
                    }
                };
                reader.onerror = function() {
                    console.error('Error reading response');
                    Swal.fire('Error', 'Failed to read server response', 'error');
                };
                reader.readAsText(response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response:', xhr.responseText);
            Swal.fire('Error', 'An error occurred while exporting to PDF: ' + error, 'error');
        }
    });
});

    // Initial load
    applyFilters();
});
    </script>
</body>
</html>
