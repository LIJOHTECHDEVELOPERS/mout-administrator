<?php
session_start();
include('db.php'); // Make sure db.php includes your MySQLi connection setup

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

// Check if the user is an admin for specific admin functions
if ($_SESSION['user_role'] !== 'admin') {
    die('Access denied: You do not have permission to access this page.');
}

// Process form submission
if (isset($_POST['add_user'])) {
    // Retrieve and sanitize input values
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $conn->real_escape_string($_POST['role']);

    // Validate role
    $valid_roles = ['admin', 'general', 'treasurer', 'familyadmin'];
    if (!in_array($role, $valid_roles)) {
        die("Invalid role selected.");
    }

    // Check if the email already exists
    $emailCheckQuery = "SELECT * FROM admin_users WHERE email = '$email'";
    $emailCheckResult = $conn->query($emailCheckQuery);

    if ($emailCheckResult->num_rows > 0) {
        die("A user with this email already exists.");
    }

    // Insert new user into the database
    $insertQuery = "INSERT INTO admin_users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
    if ($conn->query($insertQuery)) {
        echo "User added successfully!";
    } else {
        echo "Error adding user: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
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
      /* Custom styling (optional) */
      .form-label {
        font-weight: 500;
      }
      .form-control {
        margin-bottom: 1rem;
      }
      .container {
        margin-top: 30px;
      }
    </style>
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
                    <div class="row justify-content-center">
                        <div class="col-lg-6 col-md-8 col-sm-12">
                            <div class="card shadow">
                                <div class="card-body">
                                    <h3 class="card-title text-center mb-4">Add New User</h3>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name:</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email:</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password:</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role:</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="admin">Admin</option>
                                                <option value="general">General User</option>
                                                <option value="treasurer">Treasurer</option>
                                             <option value="familyadmin">Family Head</option>
                                            </select>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
