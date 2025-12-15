<?php
// includes/contact_process.php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        
        // Validation
        $errors = [];
        
        if (empty($name)) $errors[] = "Name is required";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
        if (empty($subject)) $errors[] = "Subject is required";
        if (empty($message)) $errors[] = "Message is required";
        
        if (empty($errors)) {
            // Save to database
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name,
                $email,
                $phone,
                $subject,
                $message,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            // Send email notification
            $to = getSetting('contact_email') ?: 'angelophonegate@gmail.com';
            $email_subject = "New Contact Message: " . $subject;
            $email_body = "
                New message from Angelo Phone Gate website:
                
                Name: $name
                Email: $email
                Phone: $phone
                Subject: $subject
                
                Message:
                $message
                
                IP: {$_SERVER['REMOTE_ADDR']}
                Time: " . date('Y-m-d H:i:s') . "
            ";
            
            // Send email (you can enable this later)
            // mail($to, $email_subject, $email_body);
            
            $_SESSION['success_message'] = "Thank you! Your message has been sent successfully. We'll get back to you soon!";
        } else {
            $_SESSION['error_message'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
        }
        
    } catch (Exception $e) {
        error_log("Contact form error: " . $e->getMessage());
        $_SESSION['error_message'] = "Sorry, there was an error sending your message. Please try again.";
        $_SESSION['form_data'] = $_POST;
    }
    
    header('Location: ../contact.php');
    exit;
}
?>