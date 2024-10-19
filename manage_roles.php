<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the top of the script
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include('db.php');

// Function to check access based on roles
function checkAccess($requiredRoles) {
    $userRole = $_SESSION['user_role'] ?? 'guest';
    
    if (!in_array($userRole, $requiredRoles)) {
        // Access denied, set session for notification
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Access denied!'];
        header('Location: index.php');
        exit();
    }
}

// Function to send JSON response
function sendJsonResponse($status, $message) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($isAjax) {
        sendJsonResponse('error', 'Not logged in.');
    } else {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'You must log in first.'];
        header('Location: login.php');
        exit();
    }
}

// Check user role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    if ($isAjax) {
        sendJsonResponse('error', 'Access denied: You do not have permission to access this page.');
    } else {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Access denied!'];
        header('Location: index.php');
        exit();
    }
}

// Uncomment this line to enforce role-based access
checkAccess(['admin']);

// Fetch all users
$query = "SELECT * FROM admin_users";
$result = $conn->query($query);


// Fetch all families
$familiesQuery = "SELECT id, name FROM families";
$familiesResult = $conn->query($familiesQuery);

$families = [];
if ($familiesResult) {
    while ($row = $familiesResult->fetch_assoc()) {
        $families[] = $row;
    }
}

// Handle AJAX requests
if ($isAjax) {
    // Log the received POST data for debugging
    error_log("Received POST data: " . print_r($_POST, true));

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_user':
                if (isset($_POST['user_id'], $_POST['role'], $_POST['name'], $_POST['email'], $_POST['whatsapp'])) {
                    $user_id = (int)$_POST['user_id'];
                    $new_role = $conn->real_escape_string($_POST['role']);
                    $new_name = $conn->real_escape_string($_POST['name']);
                    $new_email = $conn->real_escape_string($_POST['email']);
                    $new_whatsapp = $conn->real_escape_string($_POST['whatsapp']);
                    
                    $valid_roles = ['admin', 'general', 'treasurer', 'familyadmin'];
                    if (!in_array($new_role, $valid_roles)) {
                        sendJsonResponse('error', "Invalid role selected.");
                    }

                    $updateQuery = "UPDATE admin_users SET role = ?, name = ?, email = ?, whatsapp = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("ssssi", $new_role, $new_name, $new_email, $new_whatsapp, $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "User information updated successfully!";
                        
                        // If the role is familyadmin, update family assignments
                        if ($new_role === 'familyadmin' && isset($_POST['families'])) {
                            $families = $_POST['families'];
                            
                            // First, remove all existing assignments for this user
                            $deleteAssignments = $conn->prepare("DELETE FROM family_assignments WHERE user_id = ?");
                            $deleteAssignments->bind_param("i", $user_id);
                            $deleteAssignments->execute();
                            
                            // Then, add new assignments
                            $insertAssignment = $conn->prepare("INSERT INTO family_assignments (user_id, family_id) VALUES (?, ?)");
                            foreach ($families as $family_id) {
                                $family_id = (int)$family_id;
                                $insertAssignment->bind_param("ii", $user_id, $family_id);
                                $insertAssignment->execute();
                            }
                        }
                        
                        sendJsonResponse('success', $message);
                    } else {
                        sendJsonResponse('error', "Error updating user information: " . $conn->error);
                    }
                } else {
                    sendJsonResponse('error', 'Missing parameters for update_user action');
                }
                break;
            case 'change_password':
                if (isset($_POST['user_id'], $_POST['new_password'])) {
                    $user_id = (int)$_POST['user_id'];
                    $new_password = $conn->real_escape_string($_POST['new_password']);
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $updateQuery = "UPDATE admin_users SET password = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        sendJsonResponse('success', 'Password changed successfully!');
                    } else {
                        sendJsonResponse('error', 'Error changing password: ' . $conn->error);
                    }
                } else {
                    sendJsonResponse('error', 'Missing parameters for change_password action');
                }
                break;
            case 'reset_password':
                if (isset($_POST['user_id'])) {
                    $user_id = (int)$_POST['user_id'];
                    $new_password = bin2hex(random_bytes(8)); // Generate a random 16-character password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $updateQuery = "UPDATE admin_users SET password = ? WHERE id = ?";
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        // Fetch user details
                        $userQuery = "SELECT email, whatsapp FROM admin_users WHERE id = ?";
                        $userStmt = $conn->prepare($userQuery);
                        $userStmt->bind_param("i", $user_id);
                        $userStmt->execute();
                        $userResult = $userStmt->get_result();
                        $user = $userResult->fetch_assoc();

                        // Send email
                        $to = $user['email'];
                        $subject = "Password Reset";
                        $message = "Your new password is: " . $new_password;
                        $headers = "From: noreply@yourdomain.com";
                        if (mail($to, $subject, $message, $headers)) {
                            sendJsonResponse('success', 'Password reset and sent to user!');
                        } else {
                            sendJsonResponse('error', 'Password reset successful but email could not be sent.');
                        }
                    } else {
                        sendJsonResponse('error', 'Error resetting password: ' . $conn->error);
                    }
                } else {
                    sendJsonResponse('error', 'Missing user_id for reset_password action');
                }
                break;
            case 'delete_user':
                if (isset($_POST['user_id'])) {
                    $user_id = (int)$_POST['user_id'];
                    
                    $deleteQuery = "DELETE FROM admin_users WHERE id = ?";
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        sendJsonResponse('success', 'User deleted successfully!');
                    } else {
                        sendJsonResponse('error', 'Error deleting user: ' . $conn->error);
                    }
                } else {
                    sendJsonResponse('error', 'Missing user_id for delete_user action');
                }
                break;
            default:
                sendJsonResponse('error', 'Invalid action specified');
        }
    } else {
        sendJsonResponse('error', 'No action specified in the request');
    }
} else {
    // Non-AJAX request handling (if needed)
    // You can add code here to handle regular form submissions or page loads
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Roles</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" />
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .edit-form {
            display: none;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .btn-group-sm > .btn, .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <?php include 'header.php'; ?>
                 <div class="page-inner">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Manage Users</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>WhatsApp</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['whatsapp'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-primary btn-sm edit-button" data-id="<?php echo $row['id']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-warning btn-sm change-password-button" data-id="<?php echo $row['id']; ?>">
                                                    <i class="fas fa-key"></i> Change Password
                                                </button>
                                                <button class="btn btn-info btn-sm reset-password-button" data-id="<?php echo $row['id']; ?>">
                                                    <i class="fas fa-sync"></i> Reset & Send Password
                                                </button>
                                                <button class="btn btn-danger btn-sm delete-button" data-id="<?php echo $row['id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="edit-form" id="edit-<?php echo $row['id']; ?>">
                                        <td colspan="5">
                                            <form method="post" class="row g-3 update-user-form">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" class="form-control" name="whatsapp" value="<?php echo htmlspecialchars($row['whatsapp'] ?? ''); ?>" placeholder="WhatsApp number">
                                                </div>
                                                <div class="col-md-2">
                                                    <select name="role" class="form-select" required>
                                                        <option value="admin" <?php if ($row['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                                        <option value="general" <?php if ($row['role'] == 'general') echo 'selected'; ?>>General User</option>
                                                        <option value="treasurer" <?php if ($row['role'] == 'treasurer') echo 'selected'; ?>>Treasurer</option>
                                                        <option value="familyadmin" <?php if ($row['role'] == 'familyadmin') echo 'selected'; ?>>Family Head</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-12 mt-3 family-select" style="display: <?php echo ($row['role'] == 'familyadmin' ? 'block' : 'none'); ?>;">
                                                    <label>Assign Families:</label>
                                                    <select name="families[]" class="form-select" multiple>
                                                        <?php foreach ($families as $family): ?>
                                                            <option value="<?php echo $family['id']; ?>"><?php echo htmlspecialchars($family['name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 mt-3">
                                                    <button type="submit" name="update_user" class="btn btn-success">Update</button>
                                                </div></form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <input type="hidden" name="user_id" id="changePasswordUserId">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-button');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const editForm = document.getElementById(`edit-${userId}`);
                    editForm.style.display = editForm.style.display === 'none' ? 'table-row' : 'none';
                });
            });

            const roleSelects = document.querySelectorAll('select[name="role"]');
            roleSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const familySelect = this.closest('form').querySelector('.family-select');
                    if (this.value === 'familyadmin') {
                        familySelect.style.display = 'block';
                    } else {
                        familySelect.style.display = 'none';
                    }
                });
            });

            function sendAjaxRequest(url, formData) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                });
            }

            function displayMessage(data) {
                if (data.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: data.message,
                        position: 'topRight'
                    });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: data.message,
                        position: 'topRight'
                    });
                }
            }

            // Update user form submission
            const updateUserForms = document.querySelectorAll('.update-user-form');
            updateUserForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'update_user');
                    sendAjaxRequest('manage_roles.php', formData)
                        .then(displayMessage)
                        .catch(error => {
                            console.error('Error:', error);
                            iziToast.error({
                                title: 'Error',
                                message: 'An unexpected error occurred. Please try again.',
                                position: 'topRight'
                            });
                        });
                });
            });

            // Change Password
            const changePasswordButtons = document.querySelectorAll('.change-password-button');
            const changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            changePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    document.getElementById('changePasswordUserId').value = userId;
                    changePasswordModal.show();
                });
            });

            document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'change_password');
                sendAjaxRequest('manage_roles.php', formData)
                    .then(data => {
                        changePasswordModal.hide();
                        displayMessage(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        iziToast.error({
                            title: 'Error',
                            message: 'An unexpected error occurred. Please try again.',
                            position: 'topRight'
                        });
                    });
            });

            // Reset and Send Password
            const resetPasswordButtons = document.querySelectorAll('.reset-password-button');
            resetPasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to reset and send a new password to this user?')) {
                        const formData = new FormData();
                        formData.append('action', 'reset_password');
                        formData.append('user_id', userId);
                        sendAjaxRequest('manage_roles.php', formData)
                            .then(displayMessage)
                            .catch(error => {
                                console.error('Error:', error);
                                iziToast.error({
                                    title: 'Error',
                                    message: 'An unexpected error occurred. Please try again.',
                                    position: 'topRight'
                                });
                            });
                    }
                });
            });

            // Delete User
            const deleteButtons = document.querySelectorAll('.delete-button');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this user?')) {
                        const formData = new FormData();
                        formData.append('action', 'delete_user');
                        formData.append('user_id', userId);
                        sendAjaxRequest('manage_roles.php', formData)
                            .then(displayMessage)
                            .catch(error => {
                                console.error('Error:', error);
                                iziToast.error({
                                    title: 'Error',
                                    message: 'An unexpected error occurred. Please try again.',
                                    position: 'topRight'
                                });
                            });
                    }
                });
            });
        });
    </script>
     <script>
    <?php if (isset($_SESSION['access_denied']) && $_SESSION['access_denied']): ?>
        iziToast.error({
            title: 'Access Denied',
            message: 'You do not have permission to access that page.',
            position: 'topRight'
        });
        <?php
        // Clear the flag
        unset($_SESSION['access_denied']);
        ?>
    <?php endif; ?>
    </script>
</body>
</html>