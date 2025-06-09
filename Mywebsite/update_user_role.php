<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    // Validate role
    if (!in_array($new_role, ['client', 'admin'])) {
        $_SESSION['error'] = "Invalid role selected.";
        header('Location: manage_users.php');
        exit();
    }
    
    // Get current user's role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header('Location: manage_users.php');
        exit();
    }
    
    // Check if trying to change the last admin's role
    if ($user['role'] === 'admin' && $new_role === 'client') {
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['admin_count'];
        
        if ($count <= 1) {
            $_SESSION['error'] = "Cannot change the role of the last admin user.";
            header('Location: manage_users.php');
            exit();
        }
    }
    
    // Update user role
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User role updated successfully.";
        
        // Log the role change
        $admin_id = $_SESSION['user_id'];
        $log_message = "Admin #$admin_id changed user #$user_id role from {$user['role']} to $new_role";
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, 'role_change', ?)");
        $stmt->bind_param("is", $admin_id, $log_message);
        $stmt->execute();
    } else {
        $_SESSION['error'] = "Error updating user role: " . $conn->error;
    }
    
    header('Location: manage_users.php');
    exit();
}

// If not POST request, redirect to manage users page
header('Location: manage_users.php');
exit();
?> 