<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $receipt_code = isset($_POST['receipt_code']) ? trim($_POST['receipt_code']) : null;
    $user_id = $_SESSION['user_id'];
    
    // Validate input (receipt code is optional, so only check title and description)
    if (empty($title) || empty($description)) {
        header('Location: submit_complaint.php?error=Please fill in all required fields (Subject and Description)');
        exit();
    }
    
    // Validate receipt code if provided
    if (!empty($receipt_code)) {
        $stmt_check_receipt = $conn->prepare("SELECT COUNT(*) FROM receipts WHERE receipt_code = ?");
        $stmt_check_receipt->bind_param("s", $receipt_code);
        $stmt_check_receipt->execute();
        $stmt_check_receipt->bind_result($count);
        $stmt_check_receipt->fetch();
        $stmt_check_receipt->close();

        if ($count == 0) {
            // Receipt code does not exist
            header('Location: submit_complaint.php?error=Invalid receipt code. Please enter a valid code or leave it blank.');
            exit();
        }
    }

    // Prepare and execute the insert statement
    // Include receipt_code in the insert statement if it's not null
    if (!empty($receipt_code)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, description, receipt_code, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isss", $user_id, $title, $description, $receipt_code);
    } else {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, description, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iss", $user_id, $title, $description);
    }
    
    
    if ($stmt->execute()) {
        // Success - redirect back with success message

        // Get the ID of the newly inserted complaint
        $new_complaint_id = $conn->insert_id;

        // Prepare notification message
        $notification_message = "New complaint (#" . $new_complaint_id . ") submitted by user ID: " . $user_id . " with Subject: " . htmlspecialchars($title);

        // Fetch all admin user IDs
        $admin_users_sql = "SELECT id FROM users WHERE role = 'admin'";
        $admin_users_result = $conn->query($admin_users_sql);

        if ($admin_users_result->num_rows > 0) {
            // Prepare the notification insert statement
            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, FALSE, NOW())");

            while($admin_row = $admin_users_result->fetch_assoc()) {
                $admin_user_id = $admin_row['id'];
                // Bind parameters and execute for each admin user
                $notification_stmt->bind_param("is", $admin_user_id, $notification_message);
                $notification_stmt->execute();
            }

            $notification_stmt->close();
        }

        header('Location: submit_complaint.php?success=Complaint submitted successfully');
        exit();
    } else {
        // Error - redirect back with error message
        header('Location: submit_complaint.php?error=Error submitting complaint. Please try again.');
        exit();
    }
    
    $stmt->close();
} else {
    // If not POST request, redirect to submit page
    header('Location: submit_complaint.php');
    exit();
}

$conn->close();
?> 