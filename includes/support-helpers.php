<?php
/**
 * Klean E-Learning Platform - Support Ticket Management System
 * Layout Helpers & Core Support Functions
 */

// Bypass HTML rendering of the main SPA and load database + sessions
define('KLEAN_NO_RENDER', true);
require_once __DIR__ . '/../index.php';

// Verify user is logged in
if (!isLoggedIn()) {
    // If it's an AJAX call, return JSON
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required. Please log in first.']);
        exit;
    }
    header('Location: ../../index.php?page=login');
    exit;
}

/**
 * Log activity for a ticket
 */
function logTicketActivity($pdo, $ticketId, $activity) {
    try {
        $stmt = $pdo->prepare("INSERT INTO ticket_activity_logs (ticket_id, activity) VALUES (?, ?)");
        return $stmt->execute([$ticketId, $activity]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Log export audit log entry
 */
function logExportAudit($pdo, $adminId, $exportType) {
    try {
        $stmt = $pdo->prepare("INSERT INTO export_logs (admin_id, export_type) VALUES (?, ?)");
        return $stmt->execute([$adminId, $exportType]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Validate CSRF Token
 */
function validateTicketCSRF() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Security Error: CSRF validation failed.']);
            exit;
        }
        die("Security Error: CSRF validation failed.");
    }
}

/**
 * Render HTML Header and Navbar (matches Klean's layout)
 */
function renderTicketHeader($pageTitle) {
    global $pdo;
    $user = $_SESSION['user'];
    $roleLabel = ($user['role'] === 'admin') ? 'Admin Account' : (($user['role'] === 'instructor') ? 'Instructor Account' : 'Student Account');
    
    // Fetch notifications count
    $unreadNotifCount = 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user['id']]);
        $unreadNotifCount = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // Fallback if table doesn't exist yet
    }

    // Fetch wishlist & cart count for student
    $cartCount = 0;
    $wishCount = 0;
    if ($user['role'] === 'student') {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $cartCount = (int)$stmt->fetchColumn();

            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
            $stmt2->execute([$user['id']]);
            $wishCount = (int)$stmt2->fetchColumn();
        } catch (Exception $e) {}
    }
    
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — Klean Support</title>

  <!-- SEO -->
  <meta name="description" content="Klean E-learning support ticket management interface.">
  <meta name="theme-color" content="#6C3FF4">

  <!-- Google Fonts (Plus Jakarta Sans) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 & Bootstrap Icons CDNs -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="../../styles.css">

  <style>
    /* Extend styling for Support tickets */
    .status-badge {
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.35rem 0.65rem;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: inline-block;
    }
    .status-open { background-color: #EEF2FF; color: #4F46E5; border: 1px solid #C7D2FE; } /* Blue */
    .status-progress { background-color: #FFF7ED; color: #EA580C; border: 1px solid #FFEDD5; } /* Orange */
    .status-resolved { background-color: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; } /* Green */
    .status-closed { background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; } /* Gray */

    .priority-badge {
      font-size: 0.7rem;
      font-weight: 600;
      padding: 0.2rem 0.45rem;
      border-radius: 4px;
    }
    .priority-high { background-color: #FEF2F2; color: #DC2626; }
    .priority-medium { background-color: #FFFBEB; color: #D97706; }
    .priority-low { background-color: #F0FDF4; color: #16A34A; }

    /* Timeline Styling */
    .timeline-wrapper {
      border-left: 2px solid #E2E8F0;
      padding-left: 1.5rem;
      position: relative;
    }
    .timeline-item {
      position: relative;
      margin-bottom: 1.25rem;
    }
    .timeline-item::before {
      content: '';
      position: absolute;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: var(--primary);
      left: -25px;
      top: 5px;
      border: 2px solid #FFF;
    }
    .timeline-item.log-creation::before { background-color: #3B82F6; }
    .timeline-item.log-reply::before { background-color: #F59E0B; }
    .timeline-item.log-status::before { background-color: #10B981; }

    /* Custom scroll helper for replies list */
    .chat-scroll {
      max-height: 480px;
      overflow-y: auto;
      padding-right: 8px;
    }
    .reply-card {
      border-radius: 14px;
      border: 1px solid #E2E8F0;
      transition: all 0.2s;
    }
    .reply-card.student-reply {
      border-left: 4px solid var(--primary);
    }
    .reply-card.admin-reply {
      border-left: 4px solid #10B981;
      background-color: #FAFAF9;
    }
    .reply-card.internal-note {
      border-left: 4px solid #EF4444;
      background-color: #FEF2F2;
    }
  </style>
</head>
<body>

  <!-- STICKY NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light sticky-top klean-navbar">
    <div class="container-fluid px-4 px-lg-5">
      
      <!-- Brand Logo -->
      <a class="navbar-brand d-flex align-items-center gap-2" href="../../index.php?page=home">
        <span class="d-flex align-items-center justify-content-center rounded-3 text-white fw-800 fs-5" style="width:38px;height:38px;background:var(--primary);">K</span>
        <span class="fs-4 fw-800 text-dark" style="letter-spacing:-0.5px;">Klean<span style="color:var(--primary);">.</span></span>
      </a>
      
      <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#kleanNavContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="kleanNavContent">
        
        <!-- Search bar (dummy representation to maintain UI symmetry) -->
        <div class="my-3 my-lg-0 mx-lg-4 position-relative" style="max-width:380px; flex-grow:1;">
          <input class="form-control rounded-pill bg-light ps-4 pe-5 py-2 border-0" type="search" placeholder="Search support..." id="nav-search-input" disabled>
          <button class="btn position-absolute end-0 top-0 h-100 pe-3 text-muted border-0 bg-transparent" disabled><i class="bi bi-search"></i></button>
        </div>
        
        <!-- Nav links list -->
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <li class="nav-item"><a class="nav-link fw-600 px-2 text-dark" href="../../index.php?page=courses">Browse Courses</a></li>
          
          <?php if ($user['role'] === 'student'): ?>
            <!-- Student Links -->
            <li class="nav-item"><a class="nav-link position-relative px-2 text-dark" href="../../index.php?page=wishlist" title="Wishlist"><i class="bi bi-heart fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= ($wishCount === 0) ? 'd-none' : '' ?>" id="wishlist-badge"><?= $wishCount ?></span></a></li>
            <li class="nav-item"><a class="nav-link position-relative px-2 text-dark" href="../../index.php?page=cart" title="Cart"><i class="bi bi-cart3 fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary <?= ($cartCount === 0) ? 'd-none' : '' ?>" id="cart-badge"><?= $cartCount ?></span></a></li>
          <?php endif; ?>
          
          <!-- Notifications Bell -->
          <li class="nav-item dropdown">
            <a class="nav-link position-relative px-2 text-dark dropdown-toggle no-arrow" href="#" id="notifMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="loadNotifications()">
              <i class="bi bi-bell fs-5"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= ($unreadNotifCount === 0) ? 'd-none' : '' ?>" id="notif-badge"><?= $unreadNotifCount ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-4" style="width: 280px; max-height: 320px; overflow-y: auto;" aria-labelledby="notifMenu" id="notif-list">
              <li class="dropdown-header text-muted fw-600 py-2 border-bottom">Notifications</li>
              <li class="text-center py-3 text-muted small">Loading notifications...</li>
            </ul>
          </li>

          <!-- User dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-dark" href="#" id="navbarUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="../../<?= htmlspecialchars($user['avatar'] ?? 'default.png') ?>" alt="Avatar" class="rounded-circle border border-primary border-2" style="width:34px;height:34px;object-fit:cover;">
              <span class="fw-600 d-none d-lg-inline"><?= htmlspecialchars($user['name']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-4" aria-labelledby="navbarUserMenu">
              <li class="dropdown-header text-muted fw-500 py-1"><?= htmlspecialchars($roleLabel) ?></li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item rounded-3 py-2 fw-500" href="../../index.php?page=dashboard"><i class="bi bi-speedometer2 me-2 text-primary"></i>My Classroom</a></li>
              <li><a class="dropdown-item rounded-3 py-2 fw-500" href="../../index.php?page=profile"><i class="bi bi-gear me-2 text-muted"></i>Settings</a></li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item text-danger rounded-3 py-2 fw-500" href="#" onclick="triggerGlobalLogout(); return false;"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        </ul>

      </div>
    </div>
  </nav>

  <!-- Start Layout Wrapper -->
  <main class="d-flex flex-column flex-grow-1">
  <?php
}

/**
 * Render Sidebar for Dashboard (Student or Admin)
 */
function renderTicketSidebar($role, $activeItem) {
    if ($role === 'admin'):
    ?>
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
      <div class="sidebar-logo">
        <h6 class="fw-800 text-white mb-0"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Admin Panel</h6>
      </div>
      <a class="nav-link-klean" href="../../index.php?page=admin"><i class="bi bi-speedometer2"></i> Global Analytics</a>
      <a class="nav-link-klean" href="../../index.php?page=profile"><i class="bi bi-gear"></i> Settings</a>
      <a class="nav-link-klean <?= ($activeItem === 'support') ? 'active' : '' ?>" href="../../admin/support/index.php"><i class="bi bi-ticket-detailed"></i> Support Management</a>
      <a class="nav-link-klean <?= ($activeItem === 'reports') ? 'active' : '' ?>" href="../../admin/reports/index.php"><i class="bi bi-graph-up"></i> Reports & Exports</a>
    </div>
    <?php
    else: // Student
    ?>
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
      <div class="sidebar-logo">
        <h6 class="fw-800 text-white mb-0"><i class="bi bi-mortarboard-fill text-primary me-2"></i>My Classroom</h6>
      </div>
      <a class="nav-link-klean" href="../../index.php?page=dashboard"><i class="bi bi-speedometer2"></i> Classroom Stats</a>
      <a class="nav-link-klean" href="../../index.php?page=wishlist"><i class="bi bi-heart"></i> Bookmarks</a>
      <a class="nav-link-klean" href="../../index.php?page=profile"><i class="bi bi-gear"></i> Settings</a>
      <a class="nav-link-klean <?= ($activeItem === 'support') ? 'active' : '' ?>" href="../../student/support/index.php"><i class="bi bi-ticket-detailed"></i> Support Tickets</a>
    </div>
    <?php
    endif;
}

/**
 * Render HTML Footer and scripts
 */
function renderTicketFooter() {
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    ?>
  </main>

  <!-- Toast Container -->
  <div class="toast-container" id="supportToastContainer"></div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Config CSRF Global
    const csrfToken = "<?= $csrfToken ?>";

    // Handle logout from ticket views
    function triggerGlobalLogout() {
      if(!confirm("Are you sure you want to log out?")) return;
      const fd = new FormData();
      fd.append("csrf_token", csrfToken);
      
      fetch('../../index.php?ajax=logout', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          window.location.href = '../../index.php?page=home';
        } else {
          showToast("Logout failed. Try again.", "danger");
        }
      });
    }

    // Load dynamic notifications inside the bell menu
    function loadNotifications() {
      fetch('../../index.php?ajax=get_notifications')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            const list = document.getElementById("notif-list");
            list.innerHTML = '<li class="dropdown-header text-muted fw-600 py-2 border-bottom">Notifications</li>';
            
            // Adjust badge count
            const badge = document.getElementById("notif-badge");
            badge.textContent = res.unread;
            badge.classList.toggle("d-none", res.unread === 0);
            
            if (res.list.length === 0) {
              list.innerHTML += '<li class="text-center py-3 text-muted small">No new notifications.</li>';
              return;
            }
            
            res.list.forEach(n => {
              const bg = parseInt(n.is_read) === 0 ? 'bg-light font-weight-bold text-dark' : 'text-secondary';
              list.innerHTML += `
                <li class="p-2 border-bottom ${bg}" style="font-size:0.75rem; cursor:pointer;" onclick="markNotificationRead(${n.id})">
                  <div class="lh-sm mb-1">${escapeHtml(n.message)}</div>
                  <div class="text-muted" style="font-size:0.65rem;">${new Date(n.created_at).toLocaleDateString()}</div>
                </li>
              `;
            });
          }
        });
    }

    // Mark notification as read
    function markNotificationRead(id) {
      const fd = new FormData();
      fd.append("id", id);
      fd.append("csrf_token", csrfToken);
      
      fetch('../../index.php?ajax=mark_notification_read', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          loadNotifications();
        }
      });
    }

    // Escape HTML Helper
    function escapeHtml(str) {
      return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // Show dynamic bootstrap notifications (Toasts)
    function showToast(message, type = 'info') {
      const tc = document.getElementById("supportToastContainer");
      if (!tc) return;

      const toast = document.createElement("div");
      toast.className = `p-3 text-white border-0 rounded-4 shadow-lg ${type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-dark')}`;
      toast.style.minWidth = "260px";
      toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div class="small fw-600">${message}</div>
          <button type="button" class="btn-close btn-close-white ms-auto small shadow-none" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
      `;
      tc.appendChild(toast);
      
      // Auto dismiss after 4 seconds
      setTimeout(() => {
        toast.remove();
      }, 4000);
    }
  </script>
</body>
</html>
    <?php
}
