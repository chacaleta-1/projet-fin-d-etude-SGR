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

    if ($complaint_id > 0) {
        $stmt = $conn->prepare("DELETE FROM complaints WHERE id = ?");
        $stmt->bind_param("i", $complaint_id);

        if ($stmt->execute()) {
            header('Location: admin_dashboard.php?message=Complaint deleted successfully');
            exit();
        } else {
            header('Location: admin_dashboard.php?error=Error deleting complaint');
            exit();
        }

        $stmt->close();
    } else {
        header('Location: admin_dashboard.php?error=Invalid complaint ID');
        exit();
    }
} else {
    header('Location: admin_dashboard.php');
    exit();
}

$conn->close();
?> 