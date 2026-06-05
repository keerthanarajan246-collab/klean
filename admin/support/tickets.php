<?php
/**
 * Klean E-Learning Platform - Support Management Dashboard
 * Panel for administrators to view, search, and filter student tickets
 */

require_once __DIR__ . '/../../includes/ticket-functions.php';

// Verify admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// Fetch stats counts
try {
    $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    $openCount  = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Open'")->fetchColumn();
    $progCount  = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'In Progress'")->fetchColumn();
    $resCount   = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Resolved'")->fetchColumn();
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

// Parse Filters
$filterStatus   = sanitizeInput($_GET['status'] ?? '');
$filterPriority = sanitizeInput($_GET['priority'] ?? '');
$filterCategory = sanitizeInput($_GET['category'] ?? '');
$filterStart    = sanitizeInput($_GET['start_date'] ?? '');
$filterEnd      = sanitizeInput($_GET['end_date'] ?? '');
$searchQuery    = sanitizeInput($_GET['q'] ?? '');

// Build Dynamic SQL Query
$sql = "SELECT t.*, u.name as student_name, u.email as student_email 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $sql .= " AND t.status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterPriority)) {
    $sql .= " AND t.priority = ?";
    $params[] = $filterPriority;
}
if (!empty($filterCategory)) {
    $sql .= " AND t.category = ?";
    $params[] = $filterCategory;
}
if (!empty($filterStart)) {
    $sql .= " AND t.created_at >= ?";
    $params[] = $filterStart . ' 00:00:00';
}
if (!empty($filterEnd)) {
    $sql .= " AND t.created_at <= ?";
    $params[] = $filterEnd . ' 23:59:59';
}
if (!empty($searchQuery)) {
    if (is_numeric($searchQuery)) {
        $sql .= " AND (t.id = ? OR t.subject ILIKE ? OR u.name ILIKE ?)";
        $params[] = (int)$searchQuery;
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
    } else {
        $sql .= " AND (t.subject ILIKE ? OR u.name ILIKE ? OR t.message ILIKE ?)";
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
    }
}

// Sort by latest update or creation
$sql .= " ORDER BY t.updated_at DESC, t.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

renderTicketHeader("Support Ticket Management");
?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php renderTicketSidebar('admin', 'support'); ?>

  <!-- Main Panel -->
  <div class="dashboard-main">
    <div class="mb-4">
      <h2 class="fw-800 text-dark m-0">Support Ticket Operations</h2>
      <p class="text-muted small">Monitor, assign, reply, and resolve student platform issues.</p>
    </div>

    <!-- Stats Analytics Grid -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center">
          <h2 class="fw-800 text-dark mb-0"><?= $totalCount ?></h2>
          <span class="text-muted small fw-600">TOTAL TICKETS</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-primary border-opacity-50">
          <h2 class="fw-800 text-primary mb-0"><?= $openCount ?></h2>
          <span class="text-muted small fw-600">OPEN REQUESTS</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-warning border-opacity-50">
          <h2 class="fw-800 text-warning mb-0"><?= $progCount ?></h2>
          <span class="text-muted small fw-600">IN PROGRESS</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white border rounded-4 shadow-sm text-center border-success border-opacity-50">
          <h2 class="fw-800 text-success mb-0"><?= $resCount ?></h2>
          <span class="text-muted small fw-600">RESOLVED</span>
        </div>
      </div>
    </div>

    <!-- Filters and Search panel -->
    <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
      <h6 class="fw-800 text-dark mb-3"><i class="bi bi-funnel-fill text-muted me-2"></i>Filter & Search Tickets</h6>
      <form action="tickets.php" method="GET" class="row g-3">
        
        <!-- Search query -->
        <div class="col-md-3">
          <label class="form-label text-muted small fw-600 mb-1">SEARCH KEYWORD</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 border-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control bg-light border-start-0 border-0" name="q" placeholder="ID, Name, Subject..." value="<?= htmlspecialchars($searchQuery) ?>">
          </div>
        </div>

        <!-- Status -->
        <div class="col-md-2">
          <label class="form-label text-muted small fw-600 mb-1">STATUS</label>
          <select class="form-select bg-light border-0" name="status">
            <option value="">All Statuses</option>
            <option value="Open" <?= $filterStatus === 'Open' ? 'selected' : '' ?>>Open</option>
            <option value="In Progress" <?= $filterStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Resolved" <?= $filterStatus === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="Closed" <?= $filterStatus === 'Closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>

        <!-- Category -->
        <div class="col-md-2">
          <label class="form-label text-muted small fw-600 mb-1">CATEGORY</label>
          <select class="form-select bg-light border-0" name="category">
            <option value="">All Categories</option>
            <option value="Technical Issue" <?= $filterCategory === 'Technical Issue' ? 'selected' : '' ?>>Technical Issue</option>
            <option value="Course Access" <?= $filterCategory === 'Course Access' ? 'selected' : '' ?>>Course Access</option>
            <option value="Payment Issue" <?= $filterCategory === 'Payment Issue' ? 'selected' : '' ?>>Payment Issue</option>
            <option value="Certificate Issue" <?= $filterCategory === 'Certificate Issue' ? 'selected' : '' ?>>Certificate Issue</option>
            <option value="Account Issue" <?= $filterCategory === 'Account Issue' ? 'selected' : '' ?>>Account Issue</option>
            <option value="Other" <?= $filterCategory === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>

        <!-- Priority -->
        <div class="col-md-2">
          <label class="form-label text-muted small fw-600 mb-1">PRIORITY</label>
          <select class="form-select bg-light border-0" name="priority">
            <option value="">All Priorities</option>
            <option value="Low" <?= $filterPriority === 'Low' ? 'selected' : '' ?>>Low</option>
            <option value="Medium" <?= $filterPriority === 'Medium' ? 'selected' : '' ?>>Medium</option>
            <option value="High" <?= $filterPriority === 'High' ? 'selected' : '' ?>>High</option>
          </select>
        </div>

        <!-- Date range -->
        <div class="col-md-3">
          <label class="form-label text-muted small fw-600 mb-1">CREATED BETWEEN</label>
          <div class="d-flex align-items-center gap-2">
            <input type="date" class="form-control bg-light border-0 p-2" name="start_date" value="<?= htmlspecialchars($filterStart) ?>">
            <span class="text-muted small">to</span>
            <input type="date" class="form-control bg-light border-0 p-2" name="end_date" value="<?= htmlspecialchars($filterEnd) ?>">
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
          <a href="tickets.php" class="btn btn-outline-klean btn-sm">Clear Filters</a>
          <button type="submit" class="btn btn-primary-klean btn-sm px-4">Apply Filters</button>
        </div>
      </form>
    </div>

    <!-- Tickets List Table -->
    <div class="card p-4 border rounded-4 bg-white shadow-sm">
      <?php if (empty($tickets)): ?>
        <div class="text-center py-5">
          <div class="fs-1 text-muted bg-light rounded-circle px-3 py-2 d-inline-block mb-2">
            <i class="bi bi-filter-circle"></i>
          </div>
          <h5 class="fw-700 text-dark">No Support Tickets Found</h5>
          <p class="text-muted small mb-0">No matching tickets were found for the current filters or search query.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle table-hover mb-0" style="min-width: 900px;">
            <thead>
              <tr class="text-secondary small fw-600 border-bottom">
                <th class="py-3">ID</th>
                <th class="py-3">STUDENT</th>
                <th class="py-3">CATEGORY</th>
                <th class="py-3">PRIORITY</th>
                <th class="py-3">SUBJECT</th>
                <th class="py-3">STATUS</th>
                <th class="py-3">CREATED DATE</th>
                <th class="py-3 text-end">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): 
                $statusClass = 'status-open';
                if (strtolower($t['status']) === 'in progress') $statusClass = 'status-progress';
                if (strtolower($t['status']) === 'resolved') $statusClass = 'status-resolved';
                if (strtolower($t['status']) === 'closed') $statusClass = 'status-closed';

                $priorityClass = 'priority-medium';
                if (strtolower($t['priority']) === 'high') $priorityClass = 'priority-high';
                if (strtolower($t['priority']) === 'low') $priorityClass = 'priority-low';
              ?>
                <tr class="border-bottom">
                  <td class="py-3 fw-700 text-dark">#<?= htmlspecialchars($t['id']) ?></td>
                  <td class="py-3">
                    <span class="fw-600 text-dark d-block"><?= htmlspecialchars($t['student_name']) ?></span>
                    <span class="text-muted small" style="font-size:0.75rem;"><?= htmlspecialchars($t['student_email']) ?></span>
                  </td>
                  <td class="py-3 small text-secondary"><?= htmlspecialchars($t['category']) ?></td>
                  <td class="py-3">
                    <span class="priority-badge <?= $priorityClass ?>"><?= htmlspecialchars($t['priority']) ?></span>
                  </td>
                  <td class="py-3 fw-600 text-dark"><?= htmlspecialchars($t['subject']) ?></td>
                  <td class="py-3">
                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($t['status']) ?></span>
                  </td>
                  <td class="py-3 small text-secondary">
                    <?= date('M d, Y H:i', strtotime($t['created_at'])) ?>
                  </td>
                  <td class="py-3 text-end">
                    <a href="ticket-details.php?id=<?= $t['id'] ?>" class="btn btn-outline-klean btn-sm py-1 px-3">
                      Manage Ticket
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php
renderTicketFooter();
?>
