<?php
/**
 * Klean E-Learning Platform - Support Ticket Mailer Service
 * Integrates PHPMailer with SMTP and provides fallbacks for local testing
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// SMTP Mailer Configuration
define('MAIL_SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server host
define('MAIL_SMTP_PORT', 587);              // Change to your SMTP port (e.g. 587 for TLS, 465 for SSL)
define('MAIL_SMTP_USER', 'keerthanarajan246@gmail.com');    // Change to your SMTP username
define('MAIL_SMTP_PASS', 'xsgt ugxx zpjh nftk');    // Change to your SMTP password
define('MAIL_SMTP_SECURE', 'tls');           // 'tls' or 'ssl'
define('MAIL_FROM_EMAIL', 'support@klean.com');
define('MAIL_FROM_NAME', 'Klean Support Desk');
define('ADMIN_NOTIFICATION_EMAIL', 'keerthanarajan246@gmail.com'); // Admin email to receive ticket alerts

// Attempt to load PHPMailer
$phpmailer_loaded = false;

// Path 1: Local PHPMailer subdirectory under includes/
if (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    $phpmailer_loaded = true;
}
// Path 2: Vendor autoloader in parent directory (workspace root)
elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpmailer_loaded = true;
}
// Path 3: Vendor autoloader in current includes directory
elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $phpmailer_loaded = true;
}

/**
 * Common helper to dispatch email using PHPMailer or fall back to file logging
 */
function dispatchEmail($toEmail, $subject, $body) {
    global $phpmailer_loaded;
    
    if ($phpmailer_loaded) {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = MAIL_SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_SMTP_USER;
            $mail->Password   = MAIL_SMTP_PASS;
            $mail->Port       = MAIL_SMTP_PORT;
            
            if (MAIL_SMTP_SECURE === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (MAIL_SMTP_SECURE === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            // Recipients
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($toEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log PHPMailer SMTP error to fallback
            logEmailFallback($toEmail, "[SMTP ERROR: " . $mail->ErrorInfo . "] " . $subject, $body);
            return false;
        }
    } else {
        // Fallback: Log email details to sent_emails.log
        return logEmailFallback($toEmail, $subject, $body);
    }
}

/**
 * Fallback logger for local environments
 */
function logEmailFallback($toEmail, $subject, $body) {
    $logFile = __DIR__ . '/../sent_emails.log';
    $timestamp = date('Y-m-d H:i:s');
    $logContent = "=========================================================================\n";
    $logContent .= "TIMESTAMP: {$timestamp}\n";
    $logContent .= "TO: {$toEmail}\n";
    $logContent .= "SUBJECT: {$subject}\n";
    $logContent .= "-------------------------------------------------------------------------\n";
    $logContent .= strip_tags(str_replace(['<br>', '</p>', '<h1>', '<h2>', '<h3>'], ["\n", "\n\n", "\n=== ", " ===\n", " ===\n"], $body)) . "\n";
    $logContent .= "=========================================================================\n\n";
    
    return file_put_contents($logFile, $logContent, FILE_APPEND) !== false;
}

/**
 * Email 1: When user creates ticket, admin receives notification
 */
function sendTicketNotification($ticketId, $userName, $userEmail, $subject, $priority, $category, $message) {
    $emailSubject = "New Support Ticket: #{$ticketId} - {$subject}";
    
    $body = "
    <h2>New Support Ticket</h2>
    <p><strong>Ticket ID:</strong> #{$ticketId}</p>
    <p><strong>User:</strong> {$userName}</p>
    <p><strong>Email:</strong> {$userEmail}</p>
    <p><strong>Subject:</strong> {$subject}</p>
    <p><strong>Priority:</strong> {$priority}</p>
    <p><strong>Category:</strong> {$category}</p>
    <p><strong>Message:</strong></p>
    <p style='background-color:#F3F4F6; padding:10px; border-radius:5px;'>{$message}</p>
    ";
    
    return dispatchEmail(ADMIN_NOTIFICATION_EMAIL, $emailSubject, $body);
}

/**
 * Email 2: When admin replies, user receives notification
 */
function sendAdminReplyNotification($ticketId, $userEmail, $reply) {
    $emailSubject = "Support Ticket Update: #{$ticketId}";
    
    $body = "
    <h2>Support Ticket Update</h2>
    <p><strong>Ticket ID:</strong> #{$ticketId}</p>
    <p><strong>Admin Reply:</strong></p>
    <p style='background-color:#F3F4F6; padding:10px; border-radius:5px;'>{$reply}</p>
    <p>You can view the full conversation and reply by logging in to the platform and checking your Support Tickets dashboard.</p>
    ";
    
    return dispatchEmail($userEmail, $emailSubject, $body);
}

/**
 * Email 3: When ticket is resolved, user receives notification
 */
function sendStatusUpdateNotification($ticketId, $userEmail, $status) {
    if (strtolower($status) !== 'resolved') {
        return true; // Send notifications only for 'Resolved' as requested
    }
    
    $emailSubject = "Ticket Resolved: #{$ticketId}";
    
    $body = "
    <h2>Ticket Resolved</h2>
    <p><strong>Ticket ID:</strong> #{$ticketId}</p>
    <p><strong>Status:</strong> Resolved</p>
    <p>Thank you for contacting support.</p>
    ";
    
    return dispatchEmail($userEmail, $emailSubject, $body);
}
