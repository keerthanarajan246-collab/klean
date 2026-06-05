<?php
/**
 * Klean E-Learning Platform - Admin Ticket Management Details
 * Review ticket details, change status, add admin reply, or add internal notes
 */

require_once __DIR__ . '/../../includes/support-helpers.php';
require_once __DIR__ . '/../../includes/email-service.php';

// Verify admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$adminId = $_SESSION['user']['id'];
$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticketId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch ticket
try {
    $stmt = $pdo->prepare("SELECT t.*, u.name as student_name, u.email as student_email 
                           FROM tickets t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

$errors = [];
$success = false;

// Handle Form Submissions (Reply, Internal Note, or Status Change)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateTicketCSRF();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'post_reply') {
        $replyMessage = sanitizeInput($_POST['reply_message'] ?? '');
        $isInternal   = isset($_POST['is_internal']) && $_POST['is_internal'] == '1';
        
        if (empty($replyMessage)) {
            $errors['reply'] = "Reply message cannot be empty.";
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Insert reply
                $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, admin_id, reply_message, is_internal) VALUES (?, NULL, ?, ?, ?)");
                $stmt->execute([$ticketId, $adminId, $replyMessage, $isInternal ? 1 : 0]);
                
                // Log activity
                $activityText = $isInternal ? "Internal Note Added" : "Admin Replied";
                logTicketActivity($pdo, $ticketId, $activityText);
                
                // If this is a public reply, we automatically update the status to "In Progress" if it was "Open"
                $currentStatus = $ticket['status'];
                if (!$isInternal && $currentStatus === 'Open') {
                    $currentStatus = 'In Progress';
                }
                
                $stmtUpdate = $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$currentStatus, $ticketId]);
                
                $pdo->commit();
                
                // Send email notification to user if NOT internal
                if (!$isInternal) {
                    sendAdminReplyNotification($ticketId, $ticket['student_email'], $replyMessage);
                }
                
                header("Location: details.php?id={$ticketId}&replied=1");
                exit;
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors['db'] = "Failed to submit reply: " . $e->getMessage();
            }
        }
    } 
    elseif ($action === 'change_status') {
        $newStatus = sanitizeInput($_POST['status'] ?? '');
        $allowedStatuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
        
        if (!in_array($newStatus, $allowedStatuses)) {
            $errors['status'] = "Invalid status selected.";
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                $stmtUpdate = $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$newStatus, $ticketId]);
                
                // Log activity based on new status
                $logActivity = "Status Updated to '{$newStatus}'";
                if ($newStatus === 'Resolved') {
                    $logActivity = "Ticket Resolved";
                } elseif ($newStatus === 'Closed') {
                    $logActivity = "Ticket Closed";
                }
                
                logTicketActivity($pdo, $ticketId, $logActivity);
                
                $pdo->commit();
                
                // Send email notification if Resolved
                if ($newStatus === 'Resolved') {
                    sendStatusUpdateNotification($ticketId, $ticket['student_email'], 'Resolved');
                }
                
                header("Location: details.php?id={$ticketId}&status_updated=1");
                exit;
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors['db'] = "Failed to change status: " . $e->getMessage();
            }
        }
    }
}

// Fetch all conversation replies (including internal notes)
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name as replier_name, u.avatar as replier_avatar, u.role as replier_role 
                           FROM ticket_replies r 
                           LEFT JOIN users u ON COALESCE(r.user_id, r.admin_id) = u.id 
                           WHERE r.ticket_id = ? 
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

renderTicketHeader("Manage Ticket #{$ticketId}");
?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php renderTicketSidebar('admin', 'support'); ?>

  <!-- Main Panel -->
  <div class="dashboard-main">
    <div class="mb-4">
      <a href="index.php" class="text-primary text-decoration-none small fw-600">
        <i class="bi bi-arrow-left me-2"></i>Back to Ticket Dashboard
      </a>
      <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="fw-800 text-dark m-0">Manage Ticket: #<?= htmlspecialchars($ticket['id']) ?></h2>
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

    <!-- DB or status validation errors -->
    <?php if (!empty($errors['db'])): ?>
      <div class="alert alert-danger rounded-3 small mb-3"><?= htmlspecialchars($errors['db']) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['status'])): ?>
      <div class="alert alert-danger rounded-3 small mb-3"><?= htmlspecialchars($errors['status']) ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <!-- Ticket Details Card -->
      <div class="col-lg-8">
        <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
          <h5 class="fw-800 text-dark mb-1"><?= htmlspecialchars($ticket['subject']) ?></h5>
          <div class="mb-3">
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
          </div>
          
          <div class="text-secondary mb-0 border-top pt-3" style="font-size:0.95rem; white-space: pre-line; line-height: 1.6;">
            <strong>Initial message from student:</strong>
            <?= htmlspecialchars($ticket['message']) ?>
          </div>
        </div>
      </div>

      <!-- Quick Operations Sidebar -->
      <div class="col-lg-4">
        <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
          <h6 class="fw-800 text-dark mb-3">Ticket Coordinates</h6>
          <div class="small mb-3">
            <span class="text-muted d-block mb-1">STUDENT</span>
            <span class="fw-700 text-dark"><?= htmlspecialchars($ticket['student_name']) ?></span>
            <span class="text-muted d-block" style="font-size:0.75rem;"><?= htmlspecialchars($ticket['student_email']) ?></span>
          </div>
          <div class="small mb-3">
            <span class="text-muted d-block mb-1">DATE OPENED</span>
            <span class="fw-600 text-dark"><?= date('M d, Y H:i A', strtotime($ticket['created_at'])) ?></span>
          </div>

          <!-- Status Modification Form -->
          <form action="details.php?id=<?= $ticketId ?>" method="POST" class="border-top pt-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="change_status">
            
            <label for="status" class="form-label text-muted small fw-600 mb-1">CHANGE TICKET STATUS</label>
            <div class="d-flex gap-2">
              <select class="form-select bg-light border-0 small" id="status" name="status">
                <option value="Open" <?= $ticket['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
                <option value="In Progress" <?= $ticket['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Resolved" <?= $ticket['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="Closed" <?= $ticket['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
              </select>
              <button type="submit" class="btn btn-outline-klean btn-sm">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Conversation & Posting replies -->
      <div class="col-lg-8">
        <h5 class="fw-800 text-dark mb-3"><i class="bi bi-chat-text-fill text-primary me-2"></i>Internal & Student Conversation</h5>
        
        <div class="chat-scroll mb-4">
          <?php if (empty($replies)): ?>
            <div class="p-4 bg-light rounded-4 text-center text-muted small border">
              No replies or internal notes logged. Write a reply below to update the student.
            </div>
          <?php else: ?>
            <?php foreach ($replies as $r): 
              $isStudent = ($r['replier_role'] === 'student');
              $isInternalNote = (bool)$r['is_internal'];
              
              $cardClass = 'student-reply';
              $badgeClass = 'bg-primary bg-opacity-10 text-primary';
              $roleLabel = 'Student';
              
              if (!$isStudent) {
                  if ($isInternalNote) {
                      $cardClass = 'internal-note';
                      $badgeClass = 'bg-danger bg-opacity-10 text-danger';
                      $roleLabel = 'Internal Note';
                  } else {
                      $cardClass = 'admin-reply';
                      $badgeClass = 'bg-success bg-opacity-10 text-success';
                      $roleLabel = 'Support Specialist';
                  }
              }
            ?>
              <div class="card p-3 reply-card mb-3 <?= $cardClass ?> shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <img src="../../<?= htmlspecialchars($r['replier_avatar'] ?? 'default.png') ?>" alt="Avatar" class="rounded-circle border" style="width:28px;height:28px;object-fit:cover;">
                    <span class="fw-700 text-dark small"><?= htmlspecialchars($r['replier_name']) ?></span>
                    <span class="badge rounded-pill <?= $badgeClass ?>" style="font-size:0.6rem;"><?= $roleLabel ?></span>
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
        <h5 class="fw-800 text-dark mb-3"><i class="bi bi-reply-fill text-primary me-2"></i>Post Reply / Note</h5>
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          <?php if (!empty($errors['reply'])): ?>
            <div class="alert alert-danger rounded-3 small mb-3"><?= htmlspecialchars($errors['reply']) ?></div>
          <?php endif; ?>

          <form action="details.php?id=<?= $ticketId ?>" method="POST">
            <!-- CSRF -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="post_reply">
            
            <div class="mb-3">
              <textarea class="form-control rounded-3" name="reply_message" rows="4" placeholder="Write your message here..." required></textarea>
            </div>
            
            <div class="d-flex align-items-center justify-content-between border-top pt-3 flex-wrap gap-2">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                <label class="form-check-label small text-danger fw-600" for="is_internal">
                  <i class="bi bi-eye-slash-fill me-1"></i>Post as Admin Internal Note (Hidden from Student)
                </label>
              </div>
              <button type="submit" class="btn btn-primary-klean btn-sm px-4">
                <i class="bi bi-chat-left-text me-2"></i>Submit
              </button>
            </div>
          </form>
        </div>
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
      showToast("Reply/Note submitted successfully! ✅", "success");
    <?php endif; ?>
    <?php if (isset($_GET['status_updated'])): ?>
      showToast("Ticket status updated successfully! ✅", "success");
    <?php endif; ?>
  });
</script>

<?php
renderTicketFooter();
?>
