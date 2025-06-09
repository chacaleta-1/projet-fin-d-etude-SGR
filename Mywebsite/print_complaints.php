<?php
session_start();

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch all complaints for the logged-in user, ordered by creation date
// Join with receipts table to get receipt details
$sql = "SELECT c.*, r.product_name, r.quantity, r.purchase_date, r.amount_paid FROM complaints c LEFT JOIN receipts r ON c.receipt_code = r.receipt_code WHERE c.user_id = ? ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Complaints List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="dashboard.css"> <!-- Use dashboard.css for consistent styling -->
    <style>
        .print-section {
            margin-top: 30px;
        }

        .print-section table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .print-section th,
        .print-section td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .print-section th {
            background-color: #f2f4f8;
        }

        .print-section .status {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }

        .print-section .status.open { background-color: #dc3545; }
        .print-section .status.pending { background-color: #ffc107; }
        .print-section .status.closed { background-color: #28a745; }
        .print-section .status.resolved { background-color: #28a745; } /* Green for resolved */

        /* Styles specifically for print view */
        @media print {
            body {
                font-size: 10pt;
            }
            .sidebar, .header, .back-link, .print-button-container, .view-btn {
                display: none; /* Hide non-essential elements for print */
            }
            .main {
                 margin-left: 0; /* Remove sidebar margin for print */
                 padding: 0; /* Adjust padding for print */
            }
            .print-section table, .print-section th, .print-section td {
                border: 1px solid #000; /* Add borders for print table */
            }
            .print-section table {
                 box-shadow: none; /* Remove shadow for print */
            }
             /* Ensure checkboxes are not displayed in print */
            .print-section input[type="checkbox"] {
                display: none;
            }
             /* Hide the checkbox column header in print */
            .print-section th:first-child {
                display: none;
            }
             /* Hide the checkbox column cells in print */
            .print-section td:first-child {
                display: none;
            }
        }

    </style>
</head>
<body>
    <!-- Include client sidebar for consistent navigation -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
         <div class="sidebar">
            <div class="profile">
              <img src="img-2.png" alt="User" />
              <h3><?php echo htmlspecialchars($_SESSION['user_name']); ?>'s Dashboard</h3>
              <form method="post" action="logout.php">
                <button class="signout" type="submit">Sign Out</button>
              </form>
            </div>
            <ul>
              <li><a href="client_dashboard.php"><span class="las la-comment-dots"></span> Reclamations</a></li>
              <li><a href="settings.php"><span class="las la-sliders-h"></span> Settings</a></li>
              <li><a href="complaint_history.php"><span class="las la-history"></span> History</a></li>
              <!-- Hide print link when on this page -->
              <?php if (basename($_SERVER['PHP_SELF']) !== 'print_complaints.php'): ?>
                <li><a href="print_complaints.php"><span class="las la-print"></span> Print List</a></li>
              <?php endif; ?>
            </ul>
          </div>
    <?php endif; ?>

    <div class="main">
        <div class="header">
            <h2>Printable Complaints List</h2>
            <p>Select the complaints you want to print.</p>
        </div>

        <div class="print-section">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th> <!-- Select all checkbox -->
                        <th>Complaint ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Receipt Code</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Purchase Date</th>
                        <th>Amount Paid</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><input type="checkbox" class="complaint-checkbox" value="<?php echo $row['id']; ?>"></td>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($row['receipt_code'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['product_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['purchase_date'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['amount_paid'] ?? 'N/A'); ?></td>
                            <td><a href="view_complaint.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11">No complaints found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="print-button-container" style="text-align: center; margin-top: 20px;">
            <button id="print-selected-btn" style="padding: 10px 20px; background-color: #00c6ff; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Selected Complaints</button>
        </div>

        <a href="client_dashboard.php" class="back-link">Back to Dashboard</a>

    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('.complaint-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = e.target.checked);
        });

        document.getElementById('print-selected-btn').addEventListener('click', function() {
            const selectedIds = [];
            document.querySelectorAll('.complaint-checkbox:checked').forEach(checkbox => {
                selectedIds.push(checkbox.value);
            });

            if (selectedIds.length > 0) {
                // In a real application, you might send these IDs to a backend script
                // to generate a more tailored print view. For a simple client-side print:
                // We can hide non-selected rows and then trigger print.

                const rows = document.querySelectorAll('.print-section table tbody tr');
                rows.forEach(row => {
                    const checkbox = row.querySelector('.complaint-checkbox');
                    if (checkbox && !selectedIds.includes(checkbox.value)) {
                        row.style.display = 'none'; // Hide rows that are not selected
                    }
                });

                // Hide the checkbox column header
                document.querySelector('.print-section table th:first-child').style.display = 'none';

                 // Hide the checkboxes themselves in the print view
                document.querySelectorAll('.print-section input[type="checkbox"]').forEach(checkbox => {
                     checkbox.style.display = 'none';
                });

                // Trigger the browser's print dialog
                window.print();

                // After printing (or user cancels), refresh the page to show all rows again
                // A more sophisticated approach would restore visibility without refreshing.
                window.location.reload();

            } else {
                alert('Please select at least one complaint to print.');
            }
        });

    </script>

</body>
</html> 