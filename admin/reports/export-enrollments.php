<?php
/**
 * Klean E-Learning Platform - Enrollments CSV Exporter
 * Restricts access to Admin, exports student enrollments, logs action, and downloads as CSV
 */

require_once __DIR__ . '/../../includes/support-helpers.php';

// Verify admin role
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$adminId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT 
                                e.id as enrollment_id, 
                                u.name as student_name, 
                                u.email as student_email, 
                                c.title as course_name, 
                                e.enrolled_at, 
                                e.is_completed,
                                CASE WHEN cert.id IS NOT NULL THEN 'Issued' ELSE 'None' END as certificate_status
                           FROM enrollments e
                           JOIN users u ON e.user_id = u.id
                           JOIN courses c ON e.course_id = c.id
                           LEFT JOIN certificates cert ON e.user_id = cert.user_id AND e.course_id = cert.course_id
                           ORDER BY e.enrolled_at DESC");
    $stmt->execute();
    $enrollments = $stmt->fetchAll();
    
    // Log audit action
    logExportAudit($pdo, $adminId, 'Enrollments Data CSV Export');
    
    // Send CSV headers
    $filename = "enrollments_" . date('Y_m_d') . ".csv";
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
        'Enrollment ID', 
        'Student Name', 
        'Email', 
        'Course Name', 
        'Enrollment Date', 
        'Completion Status', 
        'Certificate Status'
    ]);
    
    // Data Rows
    foreach ($enrollments as $e) {
        $completionLabel = ((int)$e['is_completed'] === 1) ? 'Completed' : 'In Progress';
        fputcsv($output, [
            $e['enrollment_id'],
            $e['student_name'],
            $e['student_email'],
            $e['course_name'],
            date('Y-m-d H:i:s', strtotime($e['enrolled_at'])),
            $completionLabel,
            $e['certificate_status']
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Export Failed: " . $e->getMessage());
}
