<?php
session_start();
include('db.php'); // Ensure db.php includes your MySQLi connection setup

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php'); // Redirect to login page
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    
    <!-- Web Font -->
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

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <?php include 'header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Add New Member</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="index.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Forms</a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Add Member</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Member Details</div>
                                </div>
                                <div class="card-body">
                                    <form id="addMemberForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">Name</label>
                                                    <input type="text" class="form-control" id="name" placeholder="Enter Name" required />
                                                </div>
                                                <div class="form-group">
                                                    <label for="whatsapp">WhatsApp Number</label>
                                                    <input type="text" class="form-control" id="whatsapp" placeholder="Enter WhatsApp Number" required />
                                                </div>
                                                <div class="form-group">
                                                    <label for="current_year_of_study">Current Year of Study</label>
                                                    <input type="number" class="form-control" id="current_year_of_study" placeholder="Enter Current Year of Study" required />
                                                </div>
                                                <div class="form-group">
                                                    <label for="manifestJewel">Manifest or Jewel</label>
                                                    <select class="form-select" id="manifestJewel" name="manifest_jewel" required>
                                                        <option value="Manifest" selected>Manifest</option>
                                                        <option value="Jewel">Jewel</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mt-3">
                                            <button type="submit" class="btn btn-primary">Add Member</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('addMemberForm').addEventListener('submit', function (event) {
                event.preventDefault();

                var name = document.getElementById('name').value;
                var whatsapp = document.getElementById('whatsapp').value;
                var currentYearOfStudy = document.getElementById('current_year_of_study').value;
                var manifestJewel = document.getElementById('manifestJewel').value;

                var formData = new FormData();
                formData.append('name', name);
                formData.append('whatsapp', whatsapp);
                formData.append('current_year_of_study', currentYearOfStudy);
                formData.append('manifest_jewel', manifestJewel);

                fetch('add_member_script.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Member added successfully',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            document.getElementById('addMemberForm').reset();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error: ' + data.message,
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonText: 'OK'
                    });
                });
            });
        });
    </script>

    <!-- JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
</body>
</html>
