<?php
session_start();
include('db.php'); // Ensure db.php includes your MySQLi connection setup

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = $search ? "WHERE name LIKE '%$search%' OR phone LIKE '%$search%'" : '';

// Fetch associates from the database
$query = "SELECT id, name, phone, year_joined, year_left FROM associates $searchCondition ORDER BY name ASC";
$result = $conn->query($query);

// Delete functionality
if (isset($_POST['delete'])) {
    $id = $_POST['delete'];
    $deleteQuery = "DELETE FROM associates WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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
      /* Your custom CSS here */
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
          <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Associates List</h5>
                <button class="btn btn-light" id="add-associate-btn">
                    <i class="fas fa-plus"></i> Add Associate
                </button>
            </div>
            <div class="card-body">
                <form class="mb-4 search-form">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search associates" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Year Joined</th>
                                <th>Year Left</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0):
                                $counter = 1;
                                while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year_joined']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year_left']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No associates found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                            </div>
                            </div>
                            </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add Associate
            document.getElementById('add-associate-btn').addEventListener('click', function () {
                Swal.fire({
                    title: 'Add New Associate',
                    html:
                        `<input type="text" id="name" class="swal2-input" placeholder="Name" required>
                         <input type="text" id="phone" class="swal2-input" placeholder="Phone" required>
                         <input type="text" id="year_joined" class="swal2-input" placeholder="Year Joined" required>
                         <input type="text" id="year_left" class="swal2-input" placeholder="Year Left">`,
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    preConfirm: () => {
                        const name = Swal.getPopup().querySelector('#name').value;
                        const phone = Swal.getPopup().querySelector('#phone').value;
                        const year_joined = Swal.getPopup().querySelector('#year_joined').value;
                        const year_left = Swal.getPopup().querySelector('#year_left').value;
                        if (!name || !phone || !year_joined) {
                            Swal.showValidationMessage('Please fill out all required fields');
                            return;
                        }
                        return { name, phone, year_joined, year_left };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('add_associate.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(result.value)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', 'Associate added successfully!', 'success')
                                .then(() => location.reload());
                            } else {
                                Swal.fire('Error', 'Something went wrong!', 'error');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            });

            // Edit Associate
            document.querySelectorAll('.edit-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    fetch('get_associate.php?id=' + id)
                        .then(response => response.json())
                        .then(data => {
                            Swal.fire({
                                title: 'Edit Associate',
                                html:
                                    `<input type="hidden" id="edit-id" value="${data.id}">
                                     <input type="text" id="edit-name" class="swal2-input" placeholder="Name" value="${data.name}" required>
                                     <input type="text" id="edit-phone" class="swal2-input" placeholder="Phone" value="${data.phone}" required>
                                     <input type="text" id="edit-year_joined" class="swal2-input" placeholder="Year Joined" value="${data.year_joined}" required>
                                     <input type="text" id="edit-year_left" class="swal2-input" placeholder="Year Left" value="${data.year_left}">`,
                                showCancelButton: true,
                                confirmButtonText: 'Update',
                                preConfirm: () => {
                                    const id = Swal.getPopup().querySelector('#edit-id').value;
                                    const name = Swal.getPopup().querySelector('#edit-name').value;
                                    const phone = Swal.getPopup().querySelector('#edit-phone').value;
                                    const year_joined = Swal.getPopup().querySelector('#edit-year_joined').value;
                                    const year_left = Swal.getPopup().querySelector('#edit-year_left').value;
                                    if (!name || !phone || !year_joined) {
                                        Swal.showValidationMessage('Please fill out all required fields');
                                        return;
                                    }
                                    return { id, name, phone, year_joined, year_left };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch('edit_associate.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify(result.value)
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire('Updated!', 'Associate updated successfully!', 'success')
                                            .then(() => location.reload());
                                        } else {
                                            Swal.fire('Error', 'Something went wrong!', 'error');
                                        }
                                    })
                                    .catch(error => console.error('Error:', error));
                                }
                            });
                        });
                });
            });

            // Delete Associate
            document.querySelectorAll('.delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.innerHTML = `<input name="delete" value="${id}">`;
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
  </body>
</html>
