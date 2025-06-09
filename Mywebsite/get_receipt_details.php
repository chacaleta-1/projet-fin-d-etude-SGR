<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Return error or redirect, depending on desired behavior for this endpoint
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'db_connect.php';

header('Content-Type: application/json');

$receipt_code = isset($_GET['receipt_code']) ? trim($_GET['receipt_code']) : '';

if (empty($receipt_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Receipt code is required']);
    $conn->close();
    exit();
}

// Fetch receipt details
$sql = "SELECT product_name, quantity, purchase_date, amount_paid FROM receipts WHERE receipt_code = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $receipt_code);
$stmt->execute();
$result = $stmt->get_result();
$receipt_data = $result->fetch_assoc();

$stmt->close();
$conn->close();

if ($receipt_data) {
    echo json_encode(['success' => true, 'data' => $receipt_data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Receipt not found.']);
}
?> 