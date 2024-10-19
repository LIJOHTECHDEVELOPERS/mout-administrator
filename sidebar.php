<?php
// Assume you've already started the session and retrieved the user's role
session_start();
$userRole = $_SESSION['user_role'] ?? 'guest';

// Function to check if user has required role
if (!function_exists('hasRole')) {
  function hasRole($requiredRole) {
      global $userRole;
      return $userRole === $requiredRole;
  }
}

?>
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

<div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar sidebar-style-2" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="index.php" class="logo">
              <img
                src="assets/img/moutlogo.png"
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
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item">
                <a
                  data-bs-toggle="collapse"
                  href="#dashboard"
                  class="collapsed"
                  aria-expanded="false"
                >
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="dashboard">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="index.php">
                        <span class="sub-item">Home</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">MAIN SECTION</h4>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#base">
                  <i class="fas fa-user-friends"></i>
                  <p>Members</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="base">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="members.php">
                        <span class="sub-item">Members List</span>
                      </a>
                    </li>
                    <li>
                      <a href="add_member.php">
                        <span class="sub-item">Add Members</span>
                      </a>
                    </li>
                    <!--
                    <li>
                      <a href="components/gridsystem.html">
                        <span class="sub-item">Grid System</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/panels.html">
                        <span class="sub-item">Panels</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/notifications.html">
                        <span class="sub-item">Notifications</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/sweetalert.html">
                        <span class="sub-item">Sweet Alert</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/font-awesome-icons.html">
                        <span class="sub-item">Font Awesome Icons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/simple-line-icons.html">
                        <span class="sub-item">Simple Line Icons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/typography.html">
                        <span class="sub-item">Typography</span>
                      </a>
                    </li>-->
                  </ul>
                </div>
              </li>
              <li class="nav-item active submenu">
                <a data-bs-toggle="collapse" href="#sidebarLayouts">
                  <i class="fas fa-users"></i>
                  <p>Associates</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse show" id="sidebarLayouts">
                  <ul class="nav nav-collapse">
                    <li class="active">
                      <a href="associates.php">
                        <span class="sub-item">Associates List</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
               <?php if (hasRole('admin') || hasRole('treasurer')): ?>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#forms">
                  <i class="fas fa-wallet"></i>
                  <p>Accounts</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="forms">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="accounts_overview.php">
                        <span class="sub-item">Accounts</span>
                      </a>
                    </li>
                    <li>
                      <a href="transactions.php">
                        <span class="sub-item">Transactions</span>
                      </a>
                    </li>
                    <li>
                      <a href="expenses.php">
                        <span class="sub-item">Expenses</span>
                      </a>
                    </li>
                   <li>
                      <a href="withdrawals.php">
                        <span class="sub-item">Withdrawal Transactions</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <?php endif; ?>
             <li class="nav-item">
                <a data-bs-toggle="collapse" href="#maps">
                  <i class=" fas fa-money-bill-wave"></i>
                  <p>Funds Withdrawal</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="maps">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="withdrawal.php">
                        <span class="sub-item">Withdraw</span>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <span class="sub-item">Recent Withdrawals</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#tables">
                  <i class="fas fa-users"></i>
                  <p>Families</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="tables">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="family_overview.php">
                        <span class="sub-item">Overview</span>
                      </a>
                    </li>
                    <li>
                      <a href="family_page.php?family_id=1">
                        <span class="sub-item">Rehoboth</span>
                      </a>
                    </li>
                    <li>
                      <a href="family_page.php?family_id=5">
                        <span class="sub-item">Bethel</span>
                      </a>
                    </li>
                    <li>
                      <a href="family_page.php?family_id=4">
                        <span class="sub-item">Decapolians</span>
                      </a>
                    </li>
                    <li>
                      <a href="family_page.php?family_id=3">
                        <span class="sub-item">Bereans</span>
                      </a>
                    </li>
                    <li>
                      <a href="family_page.php?family_id=2">
                        <span class="sub-item">Moriah</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#maps">
                  <i class="fas fa-calendar-check"></i>
                  <p>Activities</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="maps">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="#">
                        <span class="sub-item">Overview of Activities</span>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <span class="sub-item">Add Activity</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#charts">
                  <i class="far fa-chart-bar"></i>
                  <p>Reports</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="charts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="#">
                        <span class="sub-item">General reports</span>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <span class="sub-item">individual Reports</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a href="send_message.php">
                  <i class="fas fa-paper-plane"></i>
                  <p>Message</p>
                  <span class="badge badge-success">4</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="#">
                  <i class="fas fa-file"></i>
                  <p>Mout Constitution</p>
                  <span class="badge badge-secondary">1</span>
                </a>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#submenu">
                  <i class="fas fa-user"></i>
                  <p>Users Roles</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="submenu">
                  <ul class="nav nav-collapse">
                    <li>
                      <a data-bs-toggle="collapse" href="#subnav1">
                        <span class="sub-item">Manage Users</span>
                        <span class="caret"></span>
                      </a>
                      <div class="collapse" id="subnav1">
                        <ul class="nav nav-collapse subnav">
                          <li>
                            <a href="manage_roles.php">
                              <span class="sub-item">Group User Roles</span>
                            </a>
                          </li>
                          <li>
                            <a href="add_user.php">
                              <span class="sub-item">Add Users</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </li>
      
                  </ul>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->
      <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
   

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>


<!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>
    </script>