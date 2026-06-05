<?php
/**
 * Klean E-Learning Platform - Support Tickets CSV Exporter
 * Restricts access to Admin, filters tickets data, logs action, and downloads as CSV
 */

require_once __DIR__ . '/../../includes/support-helpers.php';

// Verify admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$adminId = $_SESSION['user']['id'];

// Retrieve and sanitize filters
$status   = sanitizeInput($_GET['status'] ?? '');
$priority = sanitizeInput($_GET['priority'] ?? '');
$category = sanitizeInput($_GET['category'] ?? '');
$start    = sanitizeInput($_GET['start_date'] ?? '');
$end      = sanitizeInput($_GET['end_date'] ?? '');

// Build dynamic query
$sql = "SELECT t.id, u.name as student_name, u.email as student_email, t.subject, t.category, t.priority, t.status, t.created_at, t.updated_at 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($status)) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}
if (!empty($priority)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priority;
}
if (!empty($category)) {
    $sql .= " AND t.category = ?";
    $params[] = $category;
}
if (!empty($start)) {
    $sql .= " AND t.created_at >= ?";
    $params[] = $start . ' 00:00:00';
}
if (!empty($end)) {
    $sql .= " AND t.created_at <= ?";
    $params[] = $end . ' 23:59:59';
}

$sql .= " ORDER BY t.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
    // Log audit action
    logExportAudit($pdo, $adminId, 'Support Tickets CSV Export');
    
    // Send CSV headers
    $filename = "tickets_" . date('Y_m_d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header Row
    fputcsv($output, [
        'Ticket ID', 
        'Student Name', 
        'Student Email', 
        'Subject', 
        'Category', 
        'Priority', 
        'Status', 
        'Created Date', 
        'Updated Date'
    ]);
    
    // Data Rows
    foreach ($tickets as $t) {
        fputcsv($output, [
            $t['id'],
            $t['student_name'],
            $t['student_email'],
            $t['subject'],
            $t['category'],
            $t['priority'],
            $t['status'],
            date('Y-m-d H:i:s', strtotime($t['created_at'])),
            date('Y-m-d H:i:s', strtotime($t['updated_at']))
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Export Failed: " . $e->getMessage());
}
