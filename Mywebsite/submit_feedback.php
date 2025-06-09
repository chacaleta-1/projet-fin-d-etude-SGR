<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    if ($complaint_id > 0 && !empty($feedback)) {
        // Update the complaint with the feedback
        $sql = "UPDATE complaints SET admin_feedback = ?, feedback_date = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $feedback, $complaint_id);
        
        if ($stmt->execute()) {
            // Redirect back to the complaint view with success message
            header("Location: view_complaint_admin.php?id=" . $complaint_id . "&success=Feedback submitted successfully");
        } else {
            header("Location: view_complaint_admin.php?id=" . $complaint_id . "&error=Failed to submit feedback");
        }
        $stmt->close();
    } else {
        header("Location: view_complaint_admin.php?id=" . $complaint_id . "&error=Invalid feedback data");
    }
} else {
    header("Location: admin_dashboard.php");
}

$conn->close();
?> 