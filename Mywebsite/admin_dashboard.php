<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$sql = "SELECT complaints.*, users.first_name, users.email FROM complaints JOIN users ON complaints.user_id = users.id ORDER BY complaints.created_at DESC";
$result = $conn->query($sql);

$chart_data = [];
$chart_sql = "SELECT DATE(created_at) as complaint_date, COUNT(*) as count FROM complaints GROUP BY DATE(created_at) ORDER BY complaint_date ASC";
$chart_result = $conn->query($chart_sql);

if ($chart_result->num_rows > 0) {
    while($row = $chart_result->fetch_assoc()) {
        $chart_data[] = $row;
    }
}

$chart_data_json = json_encode($chart_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .status.open { color: #dc3545; }
    .status.pending { color: #ffc107; }
    .status.closed { color: #28a745; }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notification-bell {
        position: relative;
        cursor: pointer;
        padding: 10px;
    }
    .notification-bell .las {
        font-size: 24px;
        color: #666;
    }
    .notification-count {
        position: absolute;
        top: 0;
        right: 0;
        background: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        display: none;
    }
    .notification-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 5px;
        width: 300px;
        display: none;
        z-index: 1000;
    }
    .notification-header {
        padding: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notification-header h4 {
        margin: 0;
    }
    .notification-header button {
        background: none;
        border: none;
        color: #00c6ff;
        cursor: pointer;
    }
    .notification-list {
        max-height: 300px;
        overflow-y: auto;
    }
    .notification-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    .notification-item:hover {
        background: #f9f9f9;
    }
    .notification-item.unread {
        background: #f0f7ff;
    }
    .notification-item .time {
        font-size: 12px;
        color: #666;
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
      <li><a href="manage_users.php"><span class="las la-users"></span> Manage Users</a></li>
      <li><a href="admin_settings.php"><span class="las la-cog"></span> Settings</a></li>
      <li><a href="activity_log.php"><span class="las la-list-alt"></span> Activity Log</a></li>
    </ul>
  </div>
  <div class="main">
    <div class="header">
      <h2>Welcome, Admin 👋 <?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
      <p>Manage all user complaints.</p>
      <div class="notification-bell">
        <span class="las la-bell" id="notificationBell"></span>
        <span class="notification-count" id="notificationCount"></span>
        <div class="notification-dropdown" id="notificationDropdown">
          <div class="notification-header">
            <h4>Notifications</h4>
            <button id="markAllRead">Mark all as read</button>
          </div>
          <div class="notification-list" id="notificationList">
          </div>
        </div>
      </div>
    </div>
    <div class="chart-container" style="width: 80%; margin: 40px auto;">
        <canvas id="complaintsChart"></canvas>
    </div>
    <div class="reclamations-section">
      <table>
        <thead>
          <tr>
            <th>Complaint ID</th>
            <th>Client Name</th>
            <th>Client Email</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="reclamations-body">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['first_name']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo htmlspecialchars($row['subject']); ?></td>
              <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
              <td><?php echo htmlspecialchars($row['created_at']); ?></td>
              <td><a href="view_complaint_admin.php?id=<?php echo $row['id']; ?>" class="view-btn">View/Respond</a></td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7">No complaints found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>
</html>
<?php
$conn->close();
?>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Retrieve chart data from PHP
    const chartData = <?php echo $chart_data_json; ?>;

    // Prepare data for Chart.js
    const dates = chartData.map(item => item.complaint_date);
    const counts = chartData.map(item => item.count);

    // Get the canvas element
    const ctx = document.getElementById('complaintsChart').getContext('2d');

    const complaintsChart = new Chart(ctx, {
        type: 'line', // You can change this to 'bar' if you prefer
        data: {
            labels: dates,
            datasets: [{
                label: 'Number of Complaints',
                data: counts,
                backgroundColor: 'rgba(0, 198, 255, 0.5)', 
                borderColor: 'rgba(0, 198, 255, 1)', 
                borderWidth: 1,
                tension: 0.1 
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Complaints',
                        align: 'start',
                        padding: {left: -20}
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'start',
                    labels: {
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Complaint Trends Over Time'
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

<script>
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    const notificationCount = document.getElementById('notificationCount');
    const markAllRead = document.getElementById('markAllRead');

    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        loadNotifications();
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== bell) {
            dropdown.style.display = 'none';
        }
    });

    // Load notifications
    function loadNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                notificationList.innerHTML = '';
                let unreadCount = 0;

                data.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                    item.innerHTML = `
                        <div>${notification.message}</div>
                        <div class="time">${notification.created_at}</div>
                    `;
                    notificationList.appendChild(item);
                    if (!notification.is_read) unreadCount++;
                });

                // Update notification count
                if (unreadCount > 0) {
                    notificationCount.style.display = 'block';
                    notificationCount.textContent = unreadCount;
                } else {
                    notificationCount.style.display = 'none';
                }
            });
    }

    // Mark all as read
    markAllRead.addEventListener('click', function() {
        fetch('mark_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        });
    });

    // Initial load
    loadNotifications();
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});
</script>
