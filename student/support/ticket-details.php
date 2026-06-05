<?php
/**
 * Klean E-Learning Platform - Support Ticket Details
 * Shows conversation details, timeline, and handles student replies
 */

require_once __DIR__ . '/../../includes/ticket-functions.php';
require_once __DIR__ . '/../../includes/mailer.php';

// Verify student role
if ($_SESSION['user']['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticketId <= 0) {
    header('Location: my-tickets.php');
    exit;
}

// Fetch ticket and verify owner
try {
    $stmt = $pdo->prepare("SELECT t.*, u.name as student_name, u.email as student_email 
                           FROM tickets t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$ticketId, $userId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        // Ticket not found or not owned by user
        header('Location: my-tickets.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

$errors = [];
$success = false;

// Handle student reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    validateTicketCSRF();
    
    $replyMessage = sanitizeInput($_POST['reply_message'] ?? '');
    
    if (empty($replyMessage)) {
        $errors['reply'] = "Reply message cannot be empty.";
    }
    
    if (strtolower($ticket['status']) === 'closed') {
        $errors['reply'] = "Replies are disabled because this ticket is closed.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert reply (user_id represents the student, admin_id is null)
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, admin_id, reply_message, is_internal) VALUES (?, ?, NULL, ?, FALSE)");
            $stmt->execute([$ticketId, $userId, $replyMessage]);
            
            // Log activity
            logTicketActivity($pdo, $ticketId, "Student Replied");
            
            // Update ticket timestamp and set status back to Open or keep In Progress
            // Typically, when a student replies, if the status was Resolved, it might reopen or stay open
            $newStatus = (strtolower($ticket['status']) === 'resolved') ? 'Open' : $ticket['status'];
            $stmtUpdate = $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $ticketId]);
            
            $pdo->commit();
            
            // Send email to admin alerting about student reply (Optional but standard developer touch)
            $adminEmail = ADMIN_NOTIFICATION_EMAIL;
            $emailSubject = "Student Replied to Ticket #{$ticketId} - {$ticket['subject']}";
            $emailBody = "
            <h2>Student Replied</h2>
            <p><strong>Ticket ID:</strong> #{$ticketId}</p>
            <p><strong>Student:</strong> {$_SESSION['user']['name']}</p>
            <p><strong>Subject:</strong> {$ticket['subject']}</p>
            <p><strong>Reply Message:</strong></p>
            <p style='background-color:#F3F4F6; padding:10px; border-radius:5px;'>{$replyMessage}</p>
            ";
            dispatchEmail($adminEmail, $emailSubject, $emailBody);
            
            header("Location: ticket-details.php?id={$ticketId}&replied=1");
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['db'] = "Failed to submit reply: " . $e->getMessage();
        }
    }
}

// Fetch public conversation replies (exclude internal notes)
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name as replier_name, u.avatar as replier_avatar, u.role as replier_role 
                           FROM ticket_replies r 
                           LEFT JOIN users u ON COALESCE(r.user_id, r.admin_id) = u.id 
                           WHERE r.ticket_id = ? AND r.is_internal = FALSE 
                           ORDER BY r.created_at ASC");
    $stmt->execute([$ticketId]);
    $replies = $stmt->fetchAll();
    
    // Fetch activity logs
    $stmtLogs = $pdo->prepare("SELECT * FROM ticket_activity_logs WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmtLogs->execute([$ticketId]);
    $logs = $stmtLogs->fetchAll();
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

// Render layout
renderTicketHeader("Support Ticket #{$ticketId}");
?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php renderTicketSidebar('student', 'support'); ?>

  <!-- Main Panel -->
  <div class="dashboard-main">
    <div class="mb-4">
      <a href="my-tickets.php" class="text-primary text-decoration-none small fw-600">
        <i class="bi bi-arrow-left me-2"></i>Back to My Tickets
      </a>
      <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="fw-800 text-dark m-0">Ticket Details: #<?= htmlspecialchars($ticket['id']) ?></h2>
        <div>
          <?php 
            $statusClass = 'status-open';
            if (strtolower($ticket['status']) === 'in progress') $statusClass = 'status-progress';
            if (strtolower($ticket['status']) === 'resolved') $statusClass = 'status-resolved';
            if (strtolower($ticket['status']) === 'closed') $statusClass = 'status-closed';
          ?>
          <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($ticket['status']) ?></span>
        </div>
      </div>
    </div>

    <!-- Ticket Summary Card -->
    <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
      <div class="row g-3">
        <div class="col-md-8 border-end pe-md-4">
          <h5 class="fw-800 text-dark mb-1"><?= htmlspecialchars($ticket['subject']) ?></h5>
          <span class="text-muted small">Category: <strong><?= htmlspecialchars($ticket['category']) ?></strong></span>
          <span class="text-muted small mx-2">|</span>
          <span class="text-muted small">Priority: 
            <?php 
              $priorityClass = 'priority-medium';
              if (strtolower($ticket['priority']) === 'high') $priorityClass = 'priority-high';
              if (strtolower($ticket['priority']) === 'low') $priorityClass = 'priority-low';
            ?>
            <span class="priority-badge <?= $priorityClass ?>"><?= htmlspecialchars($ticket['priority']) ?></span>
          </span>
          
          <div class="mt-3 text-secondary" style="font-size:0.95rem; white-space: pre-line; line-height: 1.6;">
            <?= htmlspecialchars($ticket['message']) ?>
          </div>
        </div>
        <div class="col-md-4 ps-md-4">
          <h6 class="fw-800 text-dark mb-3">Ticket Information</h6>
          <div class="small mb-2">
            <span class="text-muted d-block mb-1">DATE CREATED</span>
            <span class="fw-600 text-dark"><?= date('M d, Y H:i A', strtotime($ticket['created_at'])) ?></span>
          </div>
          <div class="small mb-2">
            <span class="text-muted d-block mb-1">LAST UPDATED</span>
            <span class="fw-600 text-dark"><?= date('M d, Y H:i A', strtotime($ticket['updated_at'])) ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Chat Conversation History -->
      <div class="col-lg-8">
        <h5 class="fw-800 text-dark mb-3"><i class="bi bi-chat-text-fill text-primary me-2"></i>Conversation History</h5>
        
        <div class="chat-scroll mb-4">
          <?php if (empty($replies)): ?>
            <div class="p-4 bg-light rounded-4 text-center text-muted small border">
              No replies submitted yet. Our support agent will post an update here soon.
            </div>
          <?php else: ?>
            <?php foreach ($replies as $r): 
              $isStudentReply = ($r['replier_role'] === 'student');
              $cardClass = $isStudentReply ? 'student-reply' : 'admin-reply';
              $badgeClass = $isStudentReply ? 'bg-primary bg-opacity-10 text-primary' : 'bg-success bg-opacity-10 text-success';
              $roleName = $isStudentReply ? 'Student' : 'Support Specialist';
            ?>
              <div class="card p-3 reply-card mb-3 <?= $cardClass ?> shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <img src="../../<?= htmlspecialchars($r['replier_avatar'] ?? 'default.png') ?>" alt="Avatar" class="rounded-circle border" style="width:28px;height:28px;object-fit:cover;">
                    <span class="fw-700 text-dark small"><?= htmlspecialchars($r['replier_name']) ?></span>
                    <span class="badge rounded-pill <?= $badgeClass ?>" style="font-size:0.6rem;"><?= $roleName ?></span>
                  </div>
                  <span class="text-muted small" style="font-size: 0.72rem;"><?= date('M d, Y H:i A', strtotime($r['created_at'])) ?></span>
                </div>
                <div class="text-secondary small" style="white-space: pre-line; line-height: 1.5;">
                  <?= htmlspecialchars($r['reply_message']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Submit Reply Box -->
        <?php if (strtolower($ticket['status']) === 'closed'): ?>
          <div class="alert alert-secondary rounded-4 p-3 border" role="alert">
            <h6 class="fw-800 text-dark mb-1"><i class="bi bi-lock-fill me-2"></i>This Support Ticket is Closed</h6>
            <span class="small text-muted">This conversation is locked. If you are still encountering access or technical bugs, please open a new support ticket.</span>
          </div>
        <?php else: ?>
          <h5 class="fw-800 text-dark mb-3"><i class="bi bi-reply-fill text-primary me-2"></i>Post Reply</h5>
          <div class="card p-4 border rounded-4 bg-white shadow-sm">
            <?php if (!empty($errors['reply'])): ?>
              <div class="alert alert-danger rounded-3 small mb-3"><?= htmlspecialchars($errors['reply']) ?></div>
            <?php endif; ?>

            <form action="ticket-details.php?id=<?= $ticketId ?>" method="POST">
              <!-- CSRF -->
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              
              <div class="mb-3">
                <textarea class="form-control rounded-3" name="reply_message" rows="4" placeholder="Write your reply here..." required></textarea>
              </div>
              <button type="submit" class="btn btn-primary-klean btn-sm px-4">
                <i class="bi bi-chat-left-text me-2"></i>Submit Reply
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <!-- Ticket Activity Timeline -->
      <div class="col-lg-4 mt-4 mt-lg-0">
        <h5 class="fw-800 text-dark mb-3"><i class="bi bi-clock-history text-accent me-2"></i>Ticket Timeline</h5>
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          <div class="timeline-wrapper">
            <?php foreach ($logs as $log): 
              $logClass = 'log-creation';
              if (strpos(strtolower($log['activity']), 'reply') !== false) $logClass = 'log-reply';
              if (strpos(strtolower($log['activity']), 'status') !== false || strpos(strtolower($log['activity']), 'resolved') !== false || strpos(strtolower($log['activity']), 'closed') !== false) $logClass = 'log-status';
            ?>
              <div class="timeline-item <?= $logClass ?>">
                <span class="d-block small fw-700 text-dark"><?= htmlspecialchars($log['activity']) ?></span>
                <span class="text-muted" style="font-size:0.7rem;"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    <?php if (isset($_GET['replied'])): ?>
      showToast("Reply submitted successfully! ✅", "success");
    <?php endif; ?>
  });
</script>

<?php
renderTicketFooter();
?>
