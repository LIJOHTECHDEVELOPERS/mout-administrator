<?php
require 'db.php';


// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get user details from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'user@example.com';
$user_avatar = isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'assets/img/profile.jpg'; // Use default if not set
?>
<div class="main-header">
  <div class="main-header-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
      <a href="index.html" class="logo">
        <img
          src="assets/img/kaiadmin/logo_light.svg"
          alt="navbar brand"
          class="navbar-brand"
          height="20"
        />
      </a>
      <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
          <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
          <i class="gg-menu-left"></i>
        </button>
      </div>
      <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
      </button>
    </div>
    <!-- End Logo Header -->
  </div>
  
  <!-- Navbar Header -->
  <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
    <div class="container-fluid">
      <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
        <div class="input-group">
          <div class="input-group-prepend">
            <button type="submit" class="btn btn-search pe-1">
              <i class="fa fa-search search-icon"></i>
            </button>
          </div>
          <input type="text" placeholder="Search ..." class="form-control" />
        </div>
      </nav>

      <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
        <!-- Notification Trigger Area -->
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
             data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-bell"></i>
            <span class="notification" id="notification-count">0</span>
          </a>
          <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
            <li>
              <div class="dropdown-title" id="notification-title">No new notifications</div>
            </li>
            <li>
              <div class="notif-scroll scrollbar-outer">
                <div class="notif-center" id="notification-list">
                  <!-- Notifications will be dynamically inserted here -->
                </div>
              </div>
            </li>
            <li>
              <a class="see-all" href="javascript:void(0);">See all notifications
                <i class="fa fa-angle-right"></i>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
            <i class="fas fa-layer-group"></i>
          </a>
          <div class="dropdown-menu quick-actions animated fadeIn">
            <div class="quick-actions-header">
              <span class="title mb-1">Quick Actions</span>
              <span class="subtitle op-7">Shortcuts</span>
            </div>
            <div class="quick-actions-scroll scrollbar-outer">
              <div class="quick-actions-items">
                <div class="row m-0">
                  <a class="col-6 col-md-4 p-0" href="#">
                    <div class="quick-actions-item">
                      <div class="avatar-item bg-danger rounded-circle">
                        <i class="far fa-calendar-alt"></i>
                      </div>
                      <span class="text">Activities/Events</span>
                    </div>
                  </a>
                  <a class="col-6 col-md-4 p-0" href="#">
                    <div class="quick-actions-item">
                      <div class="avatar-item bg-warning rounded-circle">
                        <i class="fas fa-users"></i>
                      </div>
                      <span class="text">Members</span>
                    </div>
                  </a>
                  <a class="col-6 col-md-4 p-0" href="#">
                    <div class="quick-actions-item">
                      <div class="avatar-item bg-info rounded-circle">
                        <i class="fas fa-file-excel"></i>
                      </div>
                      <span class="text">Reports</span>
                    </div>
                  </a>
                  <a class="col-6 col-md-4 p-0" href="#">
                    <div class="quick-actions-item">
                      <div class="avatar-item bg-success rounded-circle">
                        <i class="fas fa-wallet"></i>
                      </div>
                      <span class="text">Accounts</span>
                    </div>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </li>

        <li class="nav-item topbar-user dropdown hidden-caret">
  <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
    <div class="avatar-sm">
      <img src="<?php echo $user_avatar; ?>" alt="..." class="avatar-img rounded-circle" />
    </div>
    <span class="profile-username">
      <span class="op-7">Hi,</span>
      <span class="fw-bold"><?php echo htmlspecialchars($user_name); ?></span>
    </span>
  </a>
  <ul class="dropdown-menu dropdown-user animated fadeIn">
    <div class="dropdown-user-scroll scrollbar-outer">
      <li>
        <div class="user-box">
          <div class="avatar-lg">
            <img src="<?php echo $user_avatar; ?>" alt="image profile" class="avatar-img rounded" />
          </div>
          <div class="u-text">
            <h4><?php echo htmlspecialchars($user_name); ?></h4>
            <p class="text-muted"><?php echo htmlspecialchars($user_email); ?></p>
            <a href="#" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
          </div>
        </div>
      </li>
      <li>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="logout.php">Logout</a>
      </li>
    </div>
  </ul>
</li>
  </nav>
  <!-- End Navbar -->
</div>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    <?php if (isset($_SESSION['notification'])): ?>
      // Insert a notification
      const notificationCount = document.getElementById('notification-count');
      const notificationTitle = document.getElementById('notification-title');
      const notificationList = document.getElementById('notification-list');
      
      // Add notification message from PHP
      const notificationMessage = "<?php echo $_SESSION['notification']; ?>";
      
      // Display the notification count
      notificationCount.textContent = '1';
      
      // Update the title and notification list
      notificationTitle.textContent = 'You have 1 new notification';
      notificationList.innerHTML = `
        <a href="#">
          <div class="notif-icon notif-primary">
            <i class="fa fa-check-circle"></i>
          </div>
          <div class="notif-content">
            <span class="block">` + notificationMessage + `</span>
            <span class="time">Just now</span>
          </div>
        </a>
      `;
      
      <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>
  });
</script>
