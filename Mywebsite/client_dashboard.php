<?php
session_start();

// Check if the user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$user_name = htmlspecialchars($_SESSION['user_name']);
$user_id = $_SESSION['user_id'];

// Fetch complaints for the logged-in user
$sql = "SELECT id, subject, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $user_name; ?>'s Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .status {
        padding: 4px 8px;
        border-radius: 4px;
        color: white;
        font-size: 12px;
    }
    .status.open { background-color: #dc3545; }
    .status.pending { background-color: #ffc107; }
    .status.closed { background-color: #28a745; }
    .status.resolved { background-color: #17a2b8; }

     .view-btn {
        display: inline-block;
        padding: 5px 10px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    .view-btn:hover {
        background-color: #0056b3;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="profile">
      <img src="img-2.png" alt="User" />
      <h3><?php echo $user_name; ?>'s Dashboard</h3>
      <form method="post" action="logout.php">
        <button class="signout" type="submit">Sign Out</button>
      </form>
    </div>
    <ul>
      <li><a href="settings.php"><span class="las la-sliders-h"></span> Settings</a></li>
      <li><a href="complaint_history.php"><span class="las la-history"></span> History</a></li>
      <li><a href="print_complaints.php"><span class="las la-print"></span> Print List</a></li>
    </ul>
  </div>
    <div class="main">
    <div class="header">
      <h2>Welcome back 👋 <?php echo $user_name; ?></h2>
      <p>Submit and manage your reclamations here.</p>
      <a href="submit_complaint.php" class="btn" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">Submit New Complaint</a>
    </div>
    <div class="reclamations-section">
      <table>
        <thead>
          <tr>
            <th>Subject</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="reclamations-body">
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                <td><span class="status <?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td><a href="view_complaint.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4">No complaints found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  </body>
</html>
 