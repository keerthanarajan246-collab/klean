<?php
/**
 * Klean E-Learning Platform - User Data CSV Exporter
 * Restricts access to Admin, exports users table, logs action, and downloads as CSV
 */

require_once __DIR__ . '/../../includes/support-helpers.php';

// Verify admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$adminId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at, is_active FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Log audit action
    logExportAudit($pdo, $adminId, 'Users List CSV Export');
    
    // Send CSV headers
    $filename = "users_" . date('Y_m_d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header Row
    fputcsv($output, [
        'User ID', 
        'Full Name', 
        'Email', 
        'Phone', 
        'Role', 
        'Registration Date', 
        'Account Status'
    ]);
    
    // Data Rows
    foreach ($users as $u) {
        $statusLabel = ((int)$u['is_active'] === 1) ? 'Active' : 'Inactive';
        fputcsv($output, [
            $u['id'],
            $u['name'],
            $u['email'],
            $u['phone'] ?? 'N/A',
            ucfirst($u['role']),
            date('Y-m-d H:i:s', strtotime($u['created_at'])),
            $statusLabel
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Export Failed: " . $e->getMessage());
}
