<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// Check if the user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Get messages from URL parameters
$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Submit Complaint</title>
  <link rel="stylesheet" href="process_complaint.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

  <div class="wrapper">
    <form action="process_complaint.php" method="POST">
      <h1>Submit a New Complaint</h1>

      <?php if ($success_message): ?>
          <div style="color: green; text-align: center; margin-bottom: 1rem; padding: 10px; background-color: #e8f5e9; border-radius: 5px;">
              <?php echo $success_message; ?>
          </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
          <div style="color: red; text-align: center; margin-bottom: 1rem; padding: 10px; background-color: #ffebee; border-radius: 5px;">
              <?php echo $error_message; ?>
          </div>
      <?php endif; ?>

      <div class="input-box">
        <input type="text" name="title" placeholder="Subject" required />
        <i class='bx bxs-edit'></i>
      </div>

      <div class="input-box">
        <textarea name="description" placeholder="Description" required style="width: 100%; height: 69px; padding: 10px; border: 1px solid rgb(204, 204, 204); border-radius: 5px;"></textarea>
        <!-- No boxicon for textarea easily, you can add one if your CSS supports it -->
      </div>

      <!-- Receipt Code Section -->
      <div class="input-box receipt-code-input-box">
        <input type="text" name="receipt_code" placeholder="Receipt Code (Optional)" />
        <i class='bx bx-receipt'></i> <!-- Using a receipt icon -->
      </div>

      <button type="submit" class="btn">Submit Complaint</button>

      <div class="register-link">
        <p><a href="client_dashboard.php">Back to Dashboard</a></p>
      </div>
    </form>
  </div>

</body>
</html> 