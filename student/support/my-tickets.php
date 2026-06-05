<?php
/**
 * Klean E-Learning Platform - My Support Tickets
 * Displays all support tickets created by the logged-in student
 */

require_once __DIR__ . '/../../includes/ticket-functions.php';

// Verify student role
if ($_SESSION['user']['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Fetch student's tickets
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

// Render layout
renderTicketHeader("My Support Tickets");
?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php renderTicketSidebar('student', 'support'); ?>

  <!-- Main Panel -->
  <div class="dashboard-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-800 text-dark m-0">Support Tickets</h2>
        <p class="text-muted small mb-0">Need help? Create a support ticket and our team will get right back to you.</p>
      </div>
      <a href="create-ticket.php" class="btn btn-primary-klean">
        <i class="bi bi-plus-circle me-2"></i>Create New Ticket
      </a>
    </div>

    <!-- Tickets List Table -->
    <div class="card p-4 border rounded-4 bg-white shadow-sm">
      <?php if (empty($tickets)): ?>
        <div class="text-center py-5">
          <div class="fs-1 text-muted bg-light rounded-circle px-3 py-2 d-inline-block mb-3">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <h5 class="fw-700 text-dark">No Tickets Opened Yet</h5>
          <p class="text-muted small mb-4">You do not have any active support requests. If you are facing any issues, let us know!</p>
          <a href="create-ticket.php" class="btn btn-outline-klean btn-sm">
            Open Your First Ticket
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle table-hover mb-0" style="min-width: 700px;">
            <thead>
              <tr class="text-secondary small fw-600 border-bottom">
                <th class="py-3">TICKET ID</th>
                <th class="py-3">SUBJECT</th>
                <th class="py-3">CATEGORY</th>
                <th class="py-3">PRIORITY</th>
                <th class="py-3">STATUS</th>
                <th class="py-3">CREATED DATE</th>
                <th class="py-3 text-end">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): 
                // Set badges based on database fields
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
                    <span class="fw-600 text-dark"><?= htmlspecialchars($t['subject']) ?></span>
                  </td>
                  <td class="py-3 small text-secondary"><?= htmlspecialchars($t['category']) ?></td>
                  <td class="py-3">
                    <span class="priority-badge <?= $priorityClass ?>"><?= htmlspecialchars($t['priority']) ?></span>
                  </td>
                  <td class="py-3">
                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($t['status']) ?></span>
                  </td>
                  <td class="py-3 small text-secondary">
                    <?= date('M d, Y H:i', strtotime($t['created_at'])) ?>
                  </td>
                  <td class="py-3 text-end">
                    <a href="ticket-details.php?id=<?= $t['id'] ?>" class="btn btn-outline-klean btn-sm py-1 px-3">
                      View Conversation
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

<script>
  document.addEventListener("DOMContentLoaded", function() {
    <?php if (isset($_GET['created'])): ?>
      showToast("Support ticket created successfully! We will contact you shortly.", "success");
    <?php endif; ?>
  });
</script>

<?php
renderTicketFooter();
?>
