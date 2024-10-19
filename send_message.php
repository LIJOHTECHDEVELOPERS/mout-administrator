<?php
require 'db.php';
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'You must be logged in to send messages.']);
    exit();
}

// Function to log errors
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

// Function to fetch users based on group and filters
function fetchUsers($conn, $group, $filters = []) {
    $query = "";
    $params = [];

    if ($group === 'members') {
        $query = "SELECT id, name, whatsapp FROM members WHERE 1=1";

        if (!empty($filters['manifest_jewel'])) {
            $query .= " AND manifest_jewel = ?";
            $params[] = $filters['manifest_jewel'];
        }
        if (!empty($filters['year_of_study'])) {
            $query .= " AND current_year_of_study = ?";
            $params[] = $filters['year_of_study'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
    } elseif ($group === 'associates') {
        $query = "SELECT id, name, phone AS whatsapp FROM associates";
    } else { // default to 'users'
        $query = "SELECT id, name, whatsapp FROM users";
    }

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// Function to send a message via WhatsApp API with retry mechanism
function sendWhatsAppMessage($api_url, $postData, $max_retries = 3) {
    $retry_count = 0;
    do {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a timeout of 30 seconds

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        if ($http_code == 200 && json_decode($response)->success) {
            return true;
        }

        $retry_count++;
        if ($retry_count < $max_retries) {
            sleep(2); // Wait for 2 seconds before retrying
        }
    } while ($retry_count < $max_retries);

    return false;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Fetch users based on selected group and filters
    if ($_POST['action'] === 'fetch_users') {
        $group = $_POST['group'] ?? 'users';
        $filters = [
            'manifest_jewel' => $_POST['manifest_jewel'] ?? '',
            'year_of_study' => $_POST['year_of_study'] ?? '',
            'status' => $_POST['status'] ?? ''
        ];

        $result = fetchUsers($conn, $group, $filters);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users);
        exit();
    }

    if ($_POST['action'] === 'send_message') {
        $message = $_POST['message'] ?? '';
        $user_ids = $_POST['user_ids'] ?? [];
        $send_to_all = isset($_POST['send_to_all']) && $_POST['send_to_all'] === 'true';
    
        if (empty($message) && !isset($_FILES['image'])) {
            echo json_encode(['error' => 'Message or image is required.']);
            exit();
        }

        $group = $_POST['group'] ?? 'users';
        $filters = [
            'manifest_jewel' => $_POST['manifest_jewel'] ?? '',
            'year_of_study' => $_POST['year_of_study'] ?? '',
            'status' => $_POST['status'] ?? ''
        ];

        // If sending to all users in the group, fetch them
        if ($send_to_all) {
            $result = fetchUsers($conn, $group, $filters);
            $user_ids = [];
            while ($row = $result->fetch_assoc()) {
                $user_ids[] = $row['whatsapp'];
            }
        }

        // WhatsApp API setup
        $api_url = 'http://34.41.242.25:3000/client/sendMessage/8b29c146-ab7e-4104-9973-5da3bd9bcf5d';
        $success_count = 0;
        $fail_count = 0;
        $error_messages = [];

        // Handle image upload
        $image_data = null;
        $image_mime = null;
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_mime = $_FILES['image']['type'];
            $image_filename = basename($_FILES['image']['name']);
            $image_data = file_get_contents($_FILES['image']['tmp_name']);
            if ($image_data === false) {
                $error_messages[] = 'Failed to read image file: ' . error_get_last()['message'];
                logError('Failed to read image file: ' . error_get_last()['message']);
            } else {
                $image_data = base64_encode($image_data);
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] != 4) {
            $error_messages[] = 'Image upload error: ' . $_FILES['image']['error'];
            logError('Image upload error: ' . $_FILES['image']['error']);
        }

        // Batch processing
        $batch_size = 10; // Adjust this value based on your API's capacity
        $user_batches = array_chunk($user_ids, $batch_size);

        foreach ($user_batches as $batch) {
            $batch_success = 0;
            $batch_fail = 0;

            foreach ($batch as $user_id) {
                // Fetch user details
                $user_query = "SELECT name, " . ($group === 'associates' ? 'phone' : 'whatsapp') . " AS whatsapp FROM " . 
                    ($group === 'associates' ? 'associates' : ($group === 'members' ? 'members' : 'users')) . 
                    " WHERE " . ($group === 'associates' ? 'phone' : 'whatsapp') . " = ?";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param('s', $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
            
                if ($user) {
                    // Remove "+" from the phone number if present
                    $whatsapp_number = str_replace('+', '', $user['whatsapp']);
            
                    // Personalize message with user name
                    $personalized_message = str_replace('{{name}}', $user['name'], $message);
                    
                    if ($image_data) {
                        // Send image with caption
                        $postData = [
                            'chatId' => $whatsapp_number . '@c.us',
                            'contentType' => 'MessageMedia',
                            'content' => [
                                'mimetype' => $image_mime,
                                'data' => $image_data,
                                'filename' => $image_filename
                            ]
                        ];
                        // Add caption if there's a message
                        if (!empty($personalized_message)) {
                            $postData['content']['caption'] = $personalized_message;
                        }
                    } else {
                        // Send text message only
                        $postData = [
                            'chatId' => $whatsapp_number . '@c.us',
                            'contentType' => 'string',
                            'content' => $personalized_message,
                        ];
                    }
            
                    if (sendWhatsAppMessage($api_url, $postData)) {
                        $batch_success++;
                    } else {
                        $batch_fail++;
                        $error_messages[] = "Failed to send message to $whatsapp_number after multiple retries";
                        logError("Failed to send message to $whatsapp_number after multiple retries");
                    }
                } else {
                    $batch_fail++;
                    $error_messages[] = "User not found for ID: $user_id";
                    logError("User not found for ID: $user_id");
                }
            }

            $success_count += $batch_success;
            $fail_count += $batch_fail;

            // Add a delay between batches to avoid overwhelming the API
            sleep(2);
        }
        
        $response = [
            'success' => "Message sent successfully to $success_count recipients.",
            'fail' => $fail_count > 0 ? "Failed to send message to $fail_count recipients." : null,
            'errors' => $error_messages
        ];
        
        echo json_encode($response);
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
      .messaging-container {
          display: flex;
          gap: 20px;
      }
      .left-column, .right-column {
          flex: 1;
      }
      .user-list {
          max-height: 300px;
          overflow-y: auto;
      }
      .btn-primary {
          background-color: #4e73df;
          border-color: #4e73df;
      }
      .btn-primary:hover {
          background-color: #2e59d9;
          border-color: #2e59d9;
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
          <h2 class="card-title mb-4">Message Center</h2>
                    <form id="sendMessageForm" enctype="multipart/form-data">
                        <div class="messaging-container">
                            <div class="left-column">
                                <div class="mb-3">
                                    <label for="group" class="form-label">Select Group:</label>
                                    <select id="group" name="group" class="form-select">
                                        <option value="users">Users</option>
                                        <option value="members">Members</option>
                                        <option value="associates">Associates</option>
                                    </select>
                                </div>
                                
                                <div id="memberFilters" style="display:none;">
                                    <div class="mb-3">
                                        <label for="manifest_jewel" class="form-label">Manifest/Jewel:</label>
                                        <select id="manifest_jewel" name="manifest_jewel" class="form-select">
                                            <option value="">All</option>
                                            <option value="manifest">Manifest</option>
                                            <option value="jewel">Jewel</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="year_of_study" class="form-label">Year of Study:</label>
                                        <select id="year_of_study" name="year_of_study" class="form-select">
                                            <option value="">All</option>
                                            <option value="1">1st Year</option>
                                            <option value="2">2nd Year</option>
                                            <option value="3">3rd Year</option>
                                            <option value="4">4th Year</option>
                                            <option value="5">5th Year</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status:</label>
                                        <select id="status" name="status" class="form-select">
                                            <option value="">All</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="user_ids" class="form-label">Select Recipients:</label>
                                    <select id="user_ids" name="user_ids[]" class="form-select" multiple size="10">
                                        <!-- User options will be dynamically populated here -->
                                    </select>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="send_to_all" name="send_to_all">
                                    <label class="form-check-label" for="send_to_all">Send to all users</label>
                                </div>
                            </div>
                            
                            <div class="right-column">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Image (optional):</label>
                                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message:</label>
                                    <textarea id="message" name="message" class="form-control" rows="8" required placeholder="Enter your message here. Use {{name}} to personalize the message."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
<script>
    $(document).ready(function() {
        function updateFilters() {
            const group = $('#group').val();
            if (group === 'members') {
                $('#memberFilters').show();
            } else {
                $('#memberFilters').hide();
            }
            fetchUsers();
        }

        function fetchUsers() {
            const formData = new FormData($('#sendMessageForm')[0]);
            formData.append('action', 'fetch_users');

            $.ajax({
                url: 'send_message.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (Array.isArray(response)) {
                        const userSelect = $('#user_ids');
                        userSelect.empty();
                        response.forEach(user => {
                            userSelect.append($('<option>', {
                                value: user.whatsapp,
                                text: `${user.name} (${user.whatsapp})`
                            }));
                        });
                    } else {
                        console.error('Unexpected response format:', response);
                        Swal.fire('Error', 'Failed to fetch users: Unexpected response format', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error fetching users:', textStatus, errorThrown);
                    Swal.fire('Error', 'Failed to fetch users: ' + textStatus, 'error');
                }
            });
        }

        $('#group, #manifest_jewel, #year_of_study, #status').change(updateFilters);

        $('#sendMessageForm').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'send_message');
            formData.append('send_to_all', $('#send_to_all').prop('checked'));

            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to send this message?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, send it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'send_message.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Sent!', response.success, 'success');
                                if (response.fail) {
                                    Swal.fire('Partial Success', response.fail, 'warning');
                                }
                                // Clear the form after successful send
                                $('#message').val('');
                                $('#image').val('');
                            } else {
                                Swal.fire('Failed', response.error || 'Unknown error occurred', 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error sending message:', textStatus, errorThrown);
                            Swal.fire('Error', 'Failed to send message: ' + textStatus, 'error');
                        }
                    });
                }
            });
        });

        // Initial fetch of users
        updateFilters();
    });
    </script>
</body>
</html>