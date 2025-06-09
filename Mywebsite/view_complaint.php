<?php
session_start();

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$complaint_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($complaint_id <= 0) {
    // Redirect back to client dashboard if no valid ID is provided
    header('Location: client_dashboard.php');
    exit();
}

// Fetch complaint details, ensuring it belongs to the logged-in user
// Also fetch the receipt_code
$sql = "SELECT complaints.*, complaints.receipt_code, users.first_name FROM complaints JOIN users ON complaints.user_id = users.id WHERE complaints.id = ? AND complaints.user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

if (!$complaint) {
    // Redirect back to client dashboard if complaint not found or doesn't belong to user
    $conn->close(); // Close connection before redirect
    header('Location: client_dashboard.php?error=Complaint not found or you do not have permission to view it.');
    exit();
}

$receipt_data = null;
// If there is a receipt code, fetch the receipt details
if (!empty($complaint['receipt_code'])) {
    $receipt_sql = "SELECT product_name, quantity, purchase_date, amount_paid FROM receipts WHERE receipt_code = ? LIMIT 1";
    $receipt_stmt = $conn->prepare($receipt_sql);
    $receipt_stmt->bind_param("s", $complaint['receipt_code']);
    $receipt_stmt->execute();
    $receipt_result = $receipt_stmt->get_result();
    $receipt_data = $receipt_result->fetch_assoc();
    $receipt_stmt->close();
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint #<?php echo htmlspecialchars($complaint['id']); ?></title>
    <link rel="stylesheet" href="dashboard.css"> <!-- Using dashboard.css for consistent client theme -->
    <style>
        .complaint-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .complaint-details h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #1e1e2f;
        }
        .complaint-info p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .complaint-info strong {
            display: inline-block;
            width: 120px;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }
        .status.open { background-color: #dc3545; }
        .status.pending { background-color: #ffc107; }
        .status.closed { background-color: #28a745; }
         .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #00c6ff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="header">
            <h2>Complaint Details</h2>
        </div>

        <div class="complaint-details">
            <h2>Complaint #<?php echo htmlspecialchars($complaint['id']); ?>: <?php echo htmlspecialchars($complaint['subject']); ?></h2>
            <div class="complaint-info">
                <p><strong>Client Name:</strong> <?php echo htmlspecialchars($complaint['first_name']); ?></p>
                <p><strong>Submitted On:</strong> <?php echo htmlspecialchars($complaint['created_at']); ?></p>
                <p><strong>Status:</strong> <span class="status <?php echo strtolower($complaint['status']); ?>"><?php echo htmlspecialchars($complaint['status']); ?></span></p>
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                
                <?php if (!empty($complaint['receipt_code'])): ?>
                    <p><strong>Receipt Code:</strong> <?php echo htmlspecialchars($complaint['receipt_code']); ?></p>
                    <?php if ($receipt_data): ?>
                        <div class="receipt-details" style="margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                            <h4>Purchase Details:</h4>
                            <p><strong>Product Name:</strong> <?php echo htmlspecialchars($receipt_data['product_name']); ?></p>
                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($receipt_data['quantity']); ?></p>
                            <p><strong>Purchase Date:</strong> <?php echo htmlspecialchars($receipt_data['purchase_date']); ?></p>
                            <p><strong>Amount Paid:</strong> <?php echo htmlspecialchars($receipt_data['amount_paid']); ?></p>
                        </div>
                    <?php else: ?>
                        <div style="color: #dc3545; margin-top: 15px;">
                            Receipt details not found for this code.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($complaint['admin_feedback'])): ?>
                    <div class="admin-feedback" style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                        <h3 style="margin-top: 0; color: #1e1e2f;">Admin Response</h3>
                        <p><?php echo nl2br(htmlspecialchars($complaint['admin_feedback'])); ?></p>
                        <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                            <em>Last updated: <?php echo htmlspecialchars($complaint['feedback_date']); ?></em>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <a href="client_dashboard.php" class="back-link">Back to Dashboard</a>

    </div>
</body>
</html> 