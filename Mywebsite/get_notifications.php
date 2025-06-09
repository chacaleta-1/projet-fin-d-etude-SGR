<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'db_connect.php';

$stmt = $conn->prepare("
    SELECT id, message, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 4
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => date('M j, Y g:i A', strtotime($row['created_at']))
    ];
}

echo json_encode($notifications);

$stmt->close();
$conn->close();
?> 