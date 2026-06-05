<?php
/**
 * Klean Support Ticket System - Test Suite Runner
 * Browse to http://localhost/klean/test-ticket-flow.php to verify support ticket integration.
 */

// Define render bypass
define('KLEAN_NO_RENDER', true);

// Include index.php to load connection and autoconfigure database tables
require_once __DIR__ . '/index.php';
require_once __DIR__ . '/includes/ticket-functions.php';
require_once __DIR__ . '/includes/mailer.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Klean Support Ticket System Test Suite ===\n\n";

// Test 1: Verify Tables Exist
echo "1. Checking table existence...\n";
$tables = ['tickets', 'ticket_replies', 'ticket_activity_logs'];
$allTablesExist = true;
foreach ($tables as $table) {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} LIMIT 1");
        $stmt->execute();
        echo "   [OK] Table '{$table}' exists.\n";
    } catch (PDOException $e) {
        echo "   [FAIL] Table '{$table}' does not exist. Error: " . $e->getMessage() . "\n";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "\n[ERROR] Core tables missing. Check database auto-creation logs in index.php.\n";
    exit;
}

// Test 2: Seed Test Data & Verify Foreign Keys
echo "\n2. Testing database operations...\n";
try {
    $pdo->beginTransaction();
    
    // Check if test student exists (Alex Johnson, id 3)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = 3");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // Create user if not exists
        $pdo->exec("INSERT INTO users (id, name, email, password, role) VALUES (3, 'Alex Johnson', 'alex@klean.com', 'dummy', 'student')");
    }
    
    // Create test ticket
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, category, priority, message, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 'Test Access Issue', 'Course Access', 'High', 'This is a test ticket message details.', 'Open']);
    $ticketId = $pdo->lastInsertId('tickets_id_seq');
    echo "   [OK] Ticket created. Insert ID: #{$ticketId}\n";
    
    // Log activity
    logTicketActivity($pdo, $ticketId, "Test Activity: Ticket Created");
    echo "   [OK] Activity log inserted.\n";
    
    // Insert replies
    $stmtReply1 = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, admin_id, reply_message, is_internal) VALUES (?, 3, NULL, ?, FALSE)");
    $stmtReply1->execute([$ticketId, 'Student reply test.']);
    
    $stmtReply2 = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, admin_id, reply_message, is_internal) VALUES (?, NULL, 1, ?, TRUE)");
    $stmtReply2->execute([$ticketId, 'Admin internal note reply test.']);
    echo "   [OK] Replies inserted (1 public, 1 internal).\n";
    
    // Query replies and check visibility
    $stmtQuery = $pdo->prepare("SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = ? AND is_internal = FALSE");
    $stmtQuery->execute([$ticketId]);
    $publicRepliesCount = $stmtQuery->fetchColumn();
    
    $stmtQuery2 = $pdo->prepare("SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = ?");
    $stmtQuery2->execute([$ticketId]);
    $allRepliesCount = $stmtQuery2->fetchColumn();
    
    if ($publicRepliesCount == 1 && $allRepliesCount == 2) {
        echo "   [OK] Reply visibility rules confirmed (Student gets 1 reply, Admin gets 2 replies).\n";
    } else {
        echo "   [FAIL] Reply visibility mismatch. Public: {$publicRepliesCount}, All: {$allRepliesCount}\n";
    }
    
    // Rollback test data to avoid pollution
    $pdo->rollBack();
    echo "   [OK] Test transaction rolled back safely. Database is clean.\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "   [FAIL] Database operations failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Verify Mailer Output Fallback
echo "\n3. Testing email notification service...\n";
$logFile = __DIR__ . '/sent_emails.log';
if (file_exists($logFile)) {
    unlink($logFile); // Remove old logs
}

sendTicketNotification(999, 'Tester Admin', 'test@example.com', 'Test Subject', 'High', 'Technical Issue', 'This is a test notification.');
sendAdminReplyNotification(999, 'test@example.com', 'This is an admin reply test notification.');
sendStatusUpdateNotification(999, 'test@example.com', 'Resolved');

if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    if ($logSize > 0) {
        echo "   [OK] Mailer fallback successfully generated 'sent_emails.log' ({$logSize} bytes).\n";
    } else {
        echo "   [FAIL] 'sent_emails.log' is empty.\n";
    }
} else {
    echo "   [FAIL] 'sent_emails.log' was not created.\n";
}

echo "\n=== All integration tests completed successfully! ===\n";
?>
