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
$sql = "SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC";
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
    <title>Complaint History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="dashboard.css"> <!-- Use dashboard.css for consistent styling -->
    <style>
        .history-section {
            margin-top: 30px;
        }

        .history-section table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .history-section th,
        .history-section td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-section th {
            background-color: #f2f4f8;
        }

        .history-section .status {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }

        .history-section .status.open { background-color: #dc3545; }
        .history-section .status.pending { background-color: #ffc107; }
        .history-section .status.closed { background-color: #28a745; }
        .history-section .status.resolved { background-color: #28a745; } /* Green for resolved */

        .history-section .view-btn {
             padding: 5px 10px;
             background: #007bff;
             border: none;
             color: white;
             border-radius: 4px;
             cursor: pointer;
             text-decoration: none;
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
              <?php if (basename($_SERVER['PHP_SELF']) !== 'complaint_history.php'): ?>
              <li><a href="client_dashboard.php"><span class="las la-comment-dots"></span> Reclamations</a></li>
              <?php endif; ?>
              <li><a href="settings.php"><span class="las la-sliders-h"></span> Settings</a></li>
              <li><a href="complaint_history.php"><span class="las la-history"></span> History</a></li>
              <?php if (basename($_SERVER['PHP_SELF']) !== 'print_complaints.php'): ?>
                <li><a href="print_complaints.php"><span class="las la-print"></span> Print List</a></li>
              <?php endif; ?>
            </ul>
          </div>
    <?php endif; ?>

    <div class="main">
        <div class="header">
            <h2>Complaint History</h2>
            <p>Here you can view all your past complaints.</p>
        </div>

        <div class="history-section">
            <table>
                <thead>
                    <tr>
                        <th>Complaint ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td><a href="view_complaint.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No complaint history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="client_dashboard.php" class="back-link">Back to Dashboard</a>

    </div>
</body>
</html> 