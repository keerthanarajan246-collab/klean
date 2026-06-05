<?php
/**
 * Klean E-Learning Platform - Courses Data CSV Exporter
 * Restricts access to Admin, exports courses data, logs action, and downloads as CSV
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
                                c.id as course_id, 
                                c.title as course_name, 
                                u.name as instructor_name, 
                                c.price, 
                                c.student_count as total_enrollments, 
                                c.created_at, 
                                c.status 
                           FROM courses c 
                           LEFT JOIN users u ON c.instructor_id = u.id 
                           ORDER BY c.created_at DESC");
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // Log audit action
    logExportAudit($pdo, $adminId, 'Courses Data CSV Export');
    
    // Send CSV headers
    $filename = "courses_" . date('Y_m_d') . ".csv";
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
        'Course ID', 
        'Course Name', 
        'Instructor', 
        'Price', 
        'Total Enrollments', 
        'Created Date', 
        'Status'
    ]);
    
    // Data Rows
    foreach ($courses as $c) {
        fputcsv($output, [
            $c['course_id'],
            $c['course_name'],
            $c['instructor_name'] ?? 'Unknown',
            $c['price'],
            $c['total_enrollments'],
            date('Y-m-d H:i:s', strtotime($c['created_at'])),
            ucfirst($c['status'])
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Export Failed: " . $e->getMessage());
}
