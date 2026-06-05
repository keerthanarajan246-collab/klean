<?php
/**
 * Klean E-Learning Platform - Create Support Ticket
 * Form for students to open a support ticket
 */

require_once __DIR__ . '/../../includes/ticket-functions.php';
require_once __DIR__ . '/../../includes/mailer.php';

// Verify student role
if ($_SESSION['user']['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$errors = [];
$success = false;

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    validateTicketCSRF();
    
    // Read and sanitize inputs
    $subject  = sanitizeInput($_POST['subject'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $priority = sanitizeInput($_POST['priority'] ?? 'Medium');
    $message  = sanitizeInput($_POST['message'] ?? '');
    
    // Field validation
    if (empty($subject)) {
        $errors['subject'] = "Subject is required.";
    }
    if (empty($category)) {
        $errors['category'] = "Category is required.";
    }
    if (empty($message)) {
        $errors['message'] = "Message content is required.";
    }
    
    $allowedCategories = ['Technical Issue', 'Course Access', 'Payment Issue', 'Certificate Issue', 'Account Issue', 'Other'];
    if (!in_array($category, $allowedCategories)) {
        $errors['category'] = "Please select a valid category.";
    }
    
    $allowedPriorities = ['Low', 'Medium', 'High'];
    if (!in_array($priority, $allowedPriorities)) {
        $errors['priority'] = "Please select a valid priority level.";
    }
    
    // If no errors, process ticket creation
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $userId = $_SESSION['user']['id'];
            
            // Insert ticket
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, category, priority, message, status) VALUES (?, ?, ?, ?, ?, 'Open')");
            $stmt->execute([$userId, $subject, $category, $priority, $message]);
            $ticketId = $pdo->lastInsertId('tickets_id_seq');
            
            // Log activity
            logTicketActivity($pdo, $ticketId, "Ticket Created");
            
            $pdo->commit();
            
            // Send email notification to admin
            $userName = $_SESSION['user']['name'];
            $userEmail = $_SESSION['user']['email'];
            sendTicketNotification($ticketId, $userName, $userEmail, $subject, $priority, $category, $message);
            
            // Redirect to list with success toast flag
            header('Location: my-tickets.php?created=1');
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['db'] = "Failed to create support ticket: " . $e->getMessage();
        }
    }
}

// Render layout
renderTicketHeader("Create Support Ticket");
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
      <h2 class="fw-800 text-dark mt-2">Open Support Ticket</h2>
      <p class="text-muted small">Please provide detailed coordinates of the issue you are experiencing so we can troubleshoot effectively.</p>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
          
          <?php if (!empty($errors['db'])): ?>
            <div class="alert alert-danger rounded-3 small mb-4" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errors['db']) ?>
            </div>
          <?php endif; ?>

          <form action="create-ticket.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Subject -->
            <div class="mb-3">
              <label for="subject" class="form-label text-muted small fw-600 mb-1">SUBJECT</label>
              <input type="text" class="form-control rounded-3 <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" id="subject" name="subject" placeholder="Summarize the issue in a few words..." value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
              <?php if (isset($errors['subject'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['subject']) ?></div>
              <?php endif; ?>
            </div>

            <div class="row g-3 mb-3">
              <!-- Category -->
              <div class="col-md-6">
                <label for="category" class="form-label text-muted small fw-600 mb-1">CATEGORY</label>
                <select class="form-select rounded-3 <?= isset($errors['category']) ? 'is-invalid' : '' ?>" id="category" name="category" required>
                  <option value="" disabled selected>Select category...</option>
                  <option value="Technical Issue" <?= ($_POST['category'] ?? '') === 'Technical Issue' ? 'selected' : '' ?>>Technical Issue</option>
                  <option value="Course Access" <?= ($_POST['category'] ?? '') === 'Course Access' ? 'selected' : '' ?>>Course Access</option>
                  <option value="Payment Issue" <?= ($_POST['category'] ?? '') === 'Payment Issue' ? 'selected' : '' ?>>Payment Issue</option>
                  <option value="Certificate Issue" <?= ($_POST['category'] ?? '') === 'Certificate Issue' ? 'selected' : '' ?>>Certificate Issue</option>
                  <option value="Account Issue" <?= ($_POST['category'] ?? '') === 'Account Issue' ? 'selected' : '' ?>>Account Issue</option>
                  <option value="Other" <?= ($_POST['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
                <?php if (isset($errors['category'])): ?>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['category']) ?></div>
                <?php endif; ?>
              </div>

              <!-- Priority -->
              <div class="col-md-6">
                <label for="priority" class="form-label text-muted small fw-600 mb-1">PRIORITY LEVEL</label>
                <select class="form-select rounded-3 <?= isset($errors['priority']) ? 'is-invalid' : '' ?>" id="priority" name="priority">
                  <option value="Low" <?= ($_POST['priority'] ?? '') === 'Low' ? 'selected' : '' ?>>Low</option>
                  <option value="Medium" <?= ($_POST['priority'] ?? 'Medium') === 'Medium' ? 'selected' : '' ?>>Medium</option>
                  <option value="High" <?= ($_POST['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                </select>
                <?php if (isset($errors['priority'])): ?>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['priority']) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Message -->
            <div class="mb-4">
              <label for="message" class="form-label text-muted small fw-600 mb-1">DETAILED DESCRIPTION</label>
              <textarea class="form-control rounded-3 <?= isset($errors['message']) ? 'is-invalid' : '' ?>" id="message" name="message" rows="6" placeholder="Describe your issue in detail. If applicable, specify which course or payment order ID you are referring to..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
              <?php if (isset($errors['message'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['message']) ?></div>
              <?php endif; ?>
            </div>

            <!-- Action buttons -->
            <div class="d-flex align-items-center gap-2 border-top pt-3">
              <button type="submit" class="btn btn-primary-klean">
                <i class="bi bi-send-fill me-2"></i>Submit Support Ticket
              </button>
              <a href="my-tickets.php" class="btn btn-outline-klean">
                Cancel
              </a>
            </div>
          </form>

        </div>
      </div>
      
      <!-- Side Guideline Widgets -->
      <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="card p-4 border rounded-4 bg-light shadow-none mb-3">
          <h6 class="fw-800 text-dark mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Before Submitting</h6>
          <ul class="small text-secondary ps-3 mb-0" style="line-height:1.6;">
            <li class="mb-1"><strong>Double check course names</strong> if you have course access issues.</li>
            <li class="mb-1">For **payment issues**, please copy-paste your exact transaction ID from your bank/receipt.</li>
            <li>For **certificate issues**, ensure you have completed **100% of the lessons** in the course.</li>
          </ul>
        </div>
        
        <div class="card p-4 border rounded-4 bg-light shadow-none">
          <h6 class="fw-800 text-dark mb-2"><i class="bi bi-clock-history text-accent me-2"></i>Response Time</h6>
          <p class="small text-secondary mb-0" style="line-height:1.5;">
            Our standard support staff review hours are Monday through Friday, 9:00 AM to 6:00 PM. High priority tickets are usually responded to within 4-6 hours. Low/Medium priority requests can take up to 24 hours.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
renderTicketFooter();
?>
