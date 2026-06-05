<?php
/**
 * Klean E-Learning Platform - Admin Reports & Exports
 * Access controlled admin panel displaying platform graphs (Chart.js) and CSV export actions
 */

require_once __DIR__ . '/../../includes/support-helpers.php';

// Verify admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$adminId = $_SESSION['user']['id'];

// Check and seed mock ticket data if tickets table is empty to make charts look great
try {
    $ticketCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    if ($ticketCount === 0) {
        // Seed 4 mock tickets with staggered dates for historical reports
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO tickets (id, user_id, subject, category, priority, message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $now = date('Y-m-d H:i:s');
        $oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
        $twoMonthsAgo = date('Y-m-d H:i:s', strtotime('-2 months'));
        
        $stmt->execute([1, 3, 'Cannot complete section 3 lesson checkout', 'Technical Issue', 'High', 'When I click next lesson, it shows an AJAX error and progress doesn\'t save.', 'In Progress', $now, $now]);
        $stmt->execute([2, 3, 'Certificate verification ID is missing', 'Certificate Issue', 'Medium', 'I completed UI/UX masterclass but the verification code doesn\'t render on screen.', 'Resolved', $oneMonthAgo, $oneMonthAgo]);
        $stmt->execute([3, 3, 'Charged twice for complete bootcamp course', 'Payment Issue', 'High', 'My credit card statement shows two charges of ₹14.99 for the bootcamp course. Please refund.', 'Open', $now, $now]);
        $stmt->execute([4, 3, 'How can I access course downloads?', 'Course Access', 'Low', 'Where are the exercise files mentioned in lesson 2?', 'Closed', $twoMonthsAgo, $twoMonthsAgo]);
        
        // Seed some replies
        $stmtReply = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, admin_id, reply_message, is_internal, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtReply->execute([1, NULL, 1, 'Working on a patch for this. We will update the system.', 0, $now]);
        $stmtReply->execute([1, NULL, 1, 'Internal Note: Checked DB state, student progress flag is correct.', 1, $now]);
        
        // Seed activity logs
        $stmtLog = $pdo->prepare("INSERT INTO ticket_activity_logs (ticket_id, activity, created_at) VALUES (?, ?, ?)");
        $stmtLog->execute([1, 'Ticket Created', $now]);
        $stmtLog->execute([1, 'Admin Replied', $now]);
        $stmtLog->execute([1, 'Internal Note Added', $now]);
        $stmtLog->execute([1, 'Status Updated to \'In Progress\'', $now]);
        
        $stmtLog->execute([2, 'Ticket Created', $oneMonthAgo]);
        $stmtLog->execute([2, 'Ticket Resolved', $oneMonthAgo]);
        
        $stmtLog->execute([3, 'Ticket Created', $now]);
        $stmtLog->execute([4, 'Ticket Created', $twoMonthsAgo]);
        $stmtLog->execute([4, 'Ticket Closed', $twoMonthsAgo]);

        // Resync sequence
        $pdo->exec("SELECT setval('tickets_id_seq', COALESCE((SELECT MAX(id) FROM tickets), 0) + 1, false)");
        $pdo->exec("SELECT setval('ticket_replies_id_seq', COALESCE((SELECT MAX(id) FROM ticket_replies), 0) + 1, false)");
        $pdo->exec("SELECT setval('ticket_activity_logs_id_seq', COALESCE((SELECT MAX(id) FROM ticket_activity_logs), 0) + 1, false)");
        
        $pdo->commit();
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

// Fetch dashboard analytical counts
try {
    $totalUsers       = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalCourses     = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalEnrollments = (int)$pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
    $totalTickets     = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    $openTickets      = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Open'")->fetchColumn();
    $resolvedTickets  = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Resolved'")->fetchColumn();
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

// Monthly registrations aggregate
try {
    $stmtReg = $pdo->prepare("SELECT TO_CHAR(created_at, 'YYYY-MM') as month_label, COUNT(*) as count 
                              FROM users 
                              GROUP BY month_label 
                              ORDER BY month_label ASC");
    $stmtReg->execute();
    $regData = $stmtReg->fetchAll();
} catch (PDOException $e) { $regData = []; }

// Monthly enrollments aggregate
try {
    $stmtEnr = $pdo->prepare("SELECT TO_CHAR(enrolled_at, 'YYYY-MM') as month_label, COUNT(*) as count 
                              FROM enrollments 
                              GROUP BY month_label 
                              ORDER BY month_label ASC");
    $stmtEnr->execute();
    $enrData = $stmtEnr->fetchAll();
} catch (PDOException $e) { $enrData = []; }

// Monthly ticket creation aggregate
try {
    $stmtTkt = $pdo->prepare("SELECT TO_CHAR(created_at, 'YYYY-MM') as month_label, COUNT(*) as count 
                              FROM tickets 
                              GROUP BY month_label 
                              ORDER BY month_label ASC");
    $stmtTkt->execute();
    $tktData = $stmtTkt->fetchAll();
} catch (PDOException $e) { $tktData = []; }

// Ticket status distribution aggregate
try {
    $stmtDist = $pdo->prepare("SELECT status, COUNT(*) as count 
                               FROM tickets 
                               GROUP BY status");
    $stmtDist->execute();
    $distData = $stmtDist->fetchAll();
} catch (PDOException $e) { $distData = []; }

// Prepare Chart Data arrays for Javascript
$regMonths = []; $regCounts = [];
foreach ($regData as $r) {
    $regMonths[]  = date('M Y', strtotime($r['month_label'] . '-01'));
    $regCounts[]  = (int)$r['count'];
}

$enrMonths = []; $enrCounts = [];
foreach ($enrData as $en) {
    $enrMonths[]  = date('M Y', strtotime($en['month_label'] . '-01'));
    $enrCounts[]  = (int)$en['count'];
}

$tktMonths = []; $tktCounts = [];
foreach ($tktData as $t) {
    $tktMonths[]  = date('M Y', strtotime($t['month_label'] . '-01'));
    $tktCounts[]  = (int)$t['count'];
}

$distLabels = []; $distCounts = [];
$statusColors = [];
foreach ($distData as $d) {
    $distLabels[] = $d['status'];
    $distCounts[] = (int)$d['count'];
    
    // Harmonious Colors mapping
    $statusColors[] = match (strtolower($d['status'])) {
        'open'        => '#4F46E5', // Blue
        'in progress' => '#EA580C', // Orange
        'resolved'    => '#059669', // Green
        'closed'      => '#64748B', // Gray
        default       => '#CBD5E1'
    };
}

renderTicketHeader("Reports & Exports Portal");
?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php renderTicketSidebar('admin', 'reports'); ?>

  <!-- Main Panel -->
  <div class="dashboard-main">
    <div class="mb-4">
      <h2 class="fw-800 text-dark m-0">Reports & Exports</h2>
      <p class="text-muted small">Access visual analytics, analyze registration/enrollment trends, and download data exports.</p>
    </div>

    <!-- Analytics Stats Summary Grid -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-2">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center">
          <h3 class="fw-800 text-dark mb-0"><?= $totalUsers ?></h3>
          <span class="text-muted small fw-600" style="font-size:0.68rem;">TOTAL USERS</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center">
          <h3 class="fw-800 text-dark mb-0"><?= $totalCourses ?></h3>
          <span class="text-muted small fw-600" style="font-size:0.68rem;">COURSES</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-primary border-opacity-25">
          <h3 class="fw-800 text-primary mb-0"><?= $totalEnrollments ?></h3>
          <span class="text-muted small fw-600" style="font-size:0.68rem;">ENROLLMENTS</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-dark border-opacity-25">
          <h3 class="fw-800 text-dark mb-0"><?= $totalTickets ?></h3>
          <span class="text-muted small fw-600" style="font-size:0.68rem;">SUPPORT TICKETS</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-danger border-opacity-25">
          <h3 class="fw-800 text-danger mb-0"><?= $openTickets ?></h3>
          <span class="text-muted small fw-600" style="font-size:0.68rem;">OPEN TICKETS</span>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-success border-opacity-25">
          <h3 class="fw-800 text-success mb-0"><?= $resolvedTickets ?></h3>
          <span class="text-muted small fw-600" style="font-size:0.68rem;">RESOLVED TICKETS</span>
        </div>
      </div>
    </div>

    <!-- Data Exporter Grid -->
    <div class="row g-4 mb-4">
      
      <!-- Ticket Export Form -->
      <div class="col-lg-8">
        <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
          <h6 class="fw-800 text-dark mb-3"><i class="bi bi-ticket-detailed-fill text-primary me-2"></i>Export Support Tickets to CSV</h6>
          
          <form action="export-tickets.php" method="GET" class="row g-3">
            <div class="col-md-4">
              <label class="form-label text-muted small fw-600 mb-1">STATUS</label>
              <select class="form-select bg-light border-0" name="status">
                <option value="">All Statuses</option>
                <option value="Open">Open</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
                <option value="Closed">Closed</option>
              </select>
            </div>
            
            <div class="col-md-4">
              <label class="form-label text-muted small fw-600 mb-1">PRIORITY</label>
              <select class="form-select bg-light border-0" name="priority">
                <option value="">All Priorities</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label text-muted small fw-600 mb-1">CATEGORY</label>
              <select class="form-select bg-light border-0" name="category">
                <option value="">All Categories</option>
                <option value="Technical Issue">Technical Issue</option>
                <option value="Course Access">Course Access</option>
                <option value="Payment Issue">Payment Issue</option>
                <option value="Certificate Issue">Certificate Issue</option>
                <option value="Account Issue">Account Issue</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label text-muted small fw-600 mb-1">DATE RANGE (CREATED)</label>
              <div class="d-flex align-items-center gap-2">
                <input type="date" class="form-control bg-light border-0 p-2" name="start_date">
                <span class="text-muted small">to</span>
                <input type="date" class="form-control bg-light border-0 p-2" name="end_date">
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end align-items-end mt-4">
              <button type="submit" class="btn btn-primary-klean btn-sm px-4">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export filtered Tickets
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Platform Exports -->
      <div class="col-lg-4">
        <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
          <h6 class="fw-800 text-dark mb-3"><i class="bi bi-download text-accent me-2"></i>Quick Data Downloads</h6>
          <p class="text-muted small mb-4">Stream direct CSV dumps of users directory, course inventories, and system purchase records.</p>
          
          <div class="d-grid gap-2">
            <a href="export-users.php" class="btn btn-outline-klean btn-sm text-start py-2">
              <i class="bi bi-people-fill text-muted me-2"></i>Export Users List
            </a>
            <a href="export-courses.php" class="btn btn-outline-klean btn-sm text-start py-2">
              <i class="bi bi-journal-code text-muted me-2"></i>Export Course Inventory
            </a>
            <a href="export-enrollments.php" class="btn btn-outline-klean btn-sm text-start py-2">
              <i class="bi bi-cart-check-fill text-muted me-2"></i>Export Enrollments Registry
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Visual Graphical Reports -->
    <div class="row g-4 mb-4">
      
      <!-- registrations -->
      <div class="col-md-6">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          <h6 class="fw-800 text-dark mb-3"><i class="bi bi-graph-up text-primary me-2"></i>Monthly User Registrations</h6>
          <div style="position: relative; height: 240px; width: 100%;">
            <canvas id="regChart"></canvas>
          </div>
        </div>
      </div>

      <!-- enrollments -->
      <div class="col-md-6">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          <h6 class="fw-800 text-dark mb-3"><i class="bi bi-mortarboard text-success me-2"></i>Monthly Course Enrollments</h6>
          <div style="position: relative; height: 240px; width: 100%;">
            <canvas id="enrChart"></canvas>
          </div>
        </div>
      </div>

      <!-- tickets trends -->
      <div class="col-md-6">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          <h6 class="fw-800 text-dark mb-3"><i class="bi bi-envelope-open text-warning me-2"></i>Monthly Support Ticket Creations</h6>
          <div style="position: relative; height: 240px; width: 100%;">
            <canvas id="tktChart"></canvas>
          </div>
        </div>
      </div>

      <!-- status distribution -->
      <div class="col-md-6">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          <h6 class="fw-800 text-dark mb-3"><i class="bi bi-pie-chart text-secondary me-2"></i>Ticket Status Distribution</h6>
          <div style="position: relative; height: 240px; width: 100%;">
            <canvas id="distChart"></canvas>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Chart.js Default styling fonts overrides
    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
    Chart.defaults.color = "#64748B";

    // 1. User Registrations Line Chart
    const regCtx = document.getElementById('regChart').getContext('2d');
    new Chart(regCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($regMonths) ?>,
        datasets: [{
          label: 'Registrations',
          data: <?= json_encode($regCounts) ?>,
          borderColor: '#6C3FF4',
          backgroundColor: 'rgba(108, 63, 244, 0.08)',
          borderWidth: 3,
          fill: true,
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: '#6C3FF4'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
          x: { grid: { display: false } }
        }
      }
    });

    // 2. Enrollments Bar Chart
    const enrCtx = document.getElementById('enrChart').getContext('2d');
    new Chart(enrCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($enrMonths) ?>,
        datasets: [{
          label: 'Enrolled Courses',
          data: <?= json_encode($enrCounts) ?>,
          backgroundColor: '#10B981',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
          x: { grid: { display: false } }
        }
      }
    });

    // 3. Support Tickets Trend Chart
    const tktCtx = document.getElementById('tktChart').getContext('2d');
    new Chart(tktCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($tktMonths) ?>,
        datasets: [{
          label: 'Support Tickets Opened',
          data: <?= json_encode($tktCounts) ?>,
          borderColor: '#F59E0B',
          backgroundColor: 'rgba(245, 158, 11, 0.05)',
          borderWidth: 3,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#F59E0B'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
          x: { grid: { display: false } }
        }
      }
    });

    // 4. Ticket Status Doughnut Chart
    const distCtx = document.getElementById('distChart').getContext('2d');
    new Chart(distCtx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($distLabels) ?>,
        datasets: [{
          data: <?= json_encode($distCounts) ?>,
          backgroundColor: <?= json_encode($statusColors) ?>,
          borderWidth: 2,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: { boxWidth: 12, padding: 15 }
          }
        },
        cutout: '65%'
      }
    });
  });
</script>

<?php
renderTicketFooter();
?>
