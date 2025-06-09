<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get complaint ID and new status from the form
    $complaint_id = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
    $new_status = isset($_POST['status']) ? $_POST['status'] : '';

    // Validate inputs
    if ($complaint_id > 0 && in_array($new_status, ['open', 'pending', 'closed', 'resolved'])) {
        // Prepare and execute the update statement
        $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $complaint_id);

        if ($stmt->execute()) {
            // Success: Redirect back to the complaint details page with a success message (optional)
            header('Location: view_complaint_admin.php?id=' . $complaint_id . '&message=Status updated successfully');
            exit();
        } else {
            // Error: Redirect back with an error message
            header('Location: view_complaint_admin.php?id=' . $complaint_id . '&error=Error updating status');
            exit();
        }

        $stmt->close();
    } else {
        // Invalid input: Redirect back with an error message
        header('Location: admin_dashboard.php?error=Invalid request');
        exit();
    }
} else {
    // Not a POST request: Redirect to admin dashboard
    header('Location: admin_dashboard.php');
    exit();
}

$conn->close();
?> 