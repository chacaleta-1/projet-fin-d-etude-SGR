<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$complaint_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($complaint_id <= 0) {
    // Redirect back to admin dashboard if no valid ID is provided
    header('Location: admin_dashboard.php');
    exit();
}

// Fetch complaint details and client info
$sql = "SELECT complaints.*, complaints.subject, users.first_name, users.email FROM complaints JOIN users ON complaints.user_id = users.id WHERE complaints.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

if (!$complaint) {
    // Redirect back to admin dashboard if complaint not found
    header('Location: admin_dashboard.php?error=Complaint not found');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    // Basic validation for status
    if (in_array($new_status, ['Open', 'Pending', 'Closed', 'Resolved'])) {
        $update_sql = "UPDATE complaints SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $complaint_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Refresh complaint data after update
        $sql = "SELECT complaints.*, users.first_name, users.email FROM complaints JOIN users ON complaints.user_id = users.id WHERE complaints.id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
    }
}

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint #<?php echo htmlspecialchars($complaint['id']); ?> - Admin</title>
    <link rel="stylesheet" href="dashboard.css"> <!-- Using dashboard.css for consistent admin theme -->
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
            width: 120px; /* Adjust as needed for alignment */
        }
        .status-update-form {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .status-update-form label {
            margin-right: 10px;
            font-weight: bold;
        }
        .status-update-form select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-right: 10px;
        }
        .status-update-form button {
            padding: 8px 15px;
            background-color: #00c6ff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .status-update-form button:hover {
            background-color: #0099cc;
        }
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
                <p><strong>Client Email:</strong> <?php echo htmlspecialchars($complaint['email']); ?></p>
                <p><strong>Submitted On:</strong> <?php echo htmlspecialchars($complaint['created_at']); ?></p>
                <p><strong>Status:</strong> <span class="status <?php echo strtolower($complaint['status']); ?>"><?php echo htmlspecialchars($complaint['status']); ?></span></p>
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                <?php if (!empty($complaint['receipt_code'])): ?>
                    <p><strong>Receipt Code:</strong> <?php echo htmlspecialchars($complaint['receipt_code']); ?></p>
                    <button id="fetch-receipt-btn" data-receipt-code="<?php echo htmlspecialchars($complaint['receipt_code']); ?>">View Receipt Details</button>
                    <div id="receipt-details" style="margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 4px; display: none;">
                        <h4>Receipt Details:</h4>
                        <p id="receipt-product"></p>
                        <p id="receipt-quantity"></p>
                        <p id="receipt-date"></p>
                        <p id="receipt-amount"></p>
                        <p id="receipt-error" style="color: red;"></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($complaint['admin_feedback'])): ?>
                    <div class="current-feedback" style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                        <p><strong>Previous Feedback:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($complaint['admin_feedback'])); ?></p>
                        <p><small>Last updated: <?php echo htmlspecialchars($complaint['feedback_date']); ?></small></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="status-update-form">
                <form action="update_complaint_status.php" method="POST">
                    <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['id']); ?>">
                    <select name="status">
                        <option value="open" <?php if ($complaint['status'] === 'open') echo 'selected'; ?>>Open</option>
                        <option value="pending" <?php if ($complaint['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="closed" <?php if ($complaint['status'] === 'closed') echo 'selected'; ?>>Closed</option>
                        <option value="resolved" <?php if ($complaint['status'] === 'resolved') echo 'selected'; ?>>Resolved</option>
                    </select>
                    <button type="submit" class="submit-btn">Update</button>
                </form>
            </div>

            <div class="feedback-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h3>Admin Feedback</h3>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="color: #28a745; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" style="color: #dc3545; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                
                <form action="submit_feedback.php" method="POST">
                    <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['id']); ?>">
                    <div style="margin-bottom: 15px;">
                        <label for="feedback" style="display: block; margin-bottom: 5px; font-weight: bold;">New Feedback:</label>
                        <textarea name="feedback" id="feedback" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required></textarea>
                    </div>
                    <button type="submit" style="padding: 8px 15px; background-color: #00c6ff; color: white; border: none; border-radius: 4px; cursor: pointer;">Submit Feedback</button>
                </form>
            </div>

            <div class="delete-section" style="margin-top: 20px;">
                <button id="delete-complaint-btn" data-complaint-id="<?php echo $complaint['id']; ?>" style="padding: 8px 15px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Delete Complaint</button>
            </div>
        </div>

        <a href="admin_dashboard.php" class="back-link">Back to Admin Dashboard</a>

    </div>

    <script>
        document.getElementById('delete-complaint-btn').addEventListener('click', function() {
            const complaintId = this.getAttribute('data-complaint-id');
            if (confirm('Are you sure you want to delete this complaint? This action cannot be undone.')) {
                // Create a form dynamically to send a POST request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_complaint.php';

                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'complaint_id';
                hiddenField.value = complaintId;

                form.appendChild(hiddenField);
                document.body.appendChild(form);
                form.submit();
            }
        });

        // JavaScript to fetch and display receipt details
        const fetchReceiptBtn = document.getElementById('fetch-receipt-btn');
        if (fetchReceiptBtn) {
            fetchReceiptBtn.addEventListener('click', function() {
                const receiptCode = this.getAttribute('data-receipt-code');
                const receiptDetailsDiv = document.getElementById('receipt-details');
                const receiptProduct = document.getElementById('receipt-product');
                const receiptQuantity = document.getElementById('receipt-quantity');
                const receiptDate = document.getElementById('receipt-date');
                const receiptAmount = document.getElementById('receipt-amount');
                const receiptError = document.getElementById('receipt-error');

                // Clear previous details and errors
                receiptProduct.textContent = '';
                receiptQuantity.textContent = '';
                receiptDate.textContent = '';
                receiptAmount.textContent = '';
                receiptError.textContent = '';
                receiptDetailsDiv.style.display = 'none'; // Hide initially

                fetch(`get_receipt_details.php?receipt_code=${encodeURIComponent(receiptCode)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            receiptProduct.textContent = `Product Name: ${data.data.product_name}`;
                            receiptQuantity.textContent = `Quantity: ${data.data.quantity}`;
                            receiptDate.textContent = `Purchase Date: ${data.data.purchase_date}`;
                            receiptAmount.textContent = `Amount Paid: ${data.data.amount_paid}`;
                            receiptDetailsDiv.style.display = 'block'; // Show details
                        } else {
                            receiptError.textContent = data.message || 'Error fetching receipt details.';
                            receiptDetailsDiv.style.display = 'block'; // Show error
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        receiptError.textContent = 'An error occurred while fetching receipt details.';
                        receiptDetailsDiv.style.display = 'block'; // Show error
                    });
            });
        }
    </script>
</body>
</html> 