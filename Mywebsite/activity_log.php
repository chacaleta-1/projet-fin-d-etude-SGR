<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

// Fetch activity log entries
$log_sql = "SELECT id, user_id, action, details, created_at FROM activity_log ORDER BY created_at DESC LIMIT 50"; // Limit to last 50 entries for performance
$log_result = $conn->query($log_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Add any specific styles for the activity log page here */
        .activity-log-section {
            margin-top: 20px;
        }
        .activity-log-section table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .activity-log-section th,
        .activity-log-section td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .activity-log-section th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="img-2.png" alt="Admin" />
            <h3>Admin Dashboard</h3>
            <form method="post" action="logout.php">
                <button class="signout" type="submit">Sign Out</button>
            </form>
        </div>
        <ul>
            <li><a href="admin_dashboard.php"><span class="las la-tachometer-alt"></span> Dashboard</a></li>
            <li><a href="manage_users.php"><span class="las la-users"></span> Manage Users</a></li>
            <li><a href="admin_settings.php"><span class="las la-cog"></span> Settings</a></li>
            <li><a href="activity_log.php" class="active"><span class="las la-list-alt"></span> Activity Log</a></li>
        </ul>
    </div>
    <div class="main">
        <div class="header">
            <h2>Activity Log</h2>
            <p>Recent system activities and user actions.</p>
            <a href="admin_dashboard.php" class="back-to-dashboard">Back to Dashboard</a>
        </div>

        <div class="activity-log-section">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User ID</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($log_result->num_rows > 0): ?>
                        <?php while ($log_row = $log_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log_row['id']); ?></td>
                            <td><?php echo htmlspecialchars($log_row['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($log_row['action']); ?></td>
                            <td><?php echo htmlspecialchars($log_row['details']); ?></td>
                            <td><?php echo htmlspecialchars($log_row['created_at']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No activity log entries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html> 