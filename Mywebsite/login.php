<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';

$email = '';
$password = '';
$error_message = '';
$inactive_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare and execute the SQL query to fetch user data
    $stmt = $conn->prepare("SELECT id, first_name, role, status, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct, check user status
            if ($user['status'] === 'active') {
                // User is active, set session variables and redirect to appropriate dashboard
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name']; // Store first name in session
                $_SESSION['user_role'] = $user['role'];

                // Update last login time
                $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();

                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: client_dashboard.php');
                }
                exit();
            } else {
                // User is inactive
                $inactive_message = "Your account is currently inactive. Please contact the administrator to reactivate it.";
                
                // Log the inactive login attempt
                $log_message = "Inactive user login attempt for email: " . $email;
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
                $log_action = 'inactive_login_attempt';
                $log_stmt->bind_param("iss", $user['id'], $log_action, $log_message);
                $log_stmt->execute();
                $log_stmt->close();

                // --- Add Notification for Admin ---
                $notification_message = "Inactive user (Email: " . htmlspecialchars($email) . ") attempted to log in.";
                
                // Fetch all admin user IDs
                $admin_users_sql = "SELECT id FROM users WHERE role = 'admin'";
                $admin_users_result = $conn->query($admin_users_sql);

                if ($admin_users_result->num_rows > 0) {
                    $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, FALSE, NOW())");

                    while($admin_row = $admin_users_result->fetch_assoc()) {
                        $admin_user_id = $admin_row['id'];
                        $notification_stmt->bind_param("is", $admin_user_id, $notification_message);
                        $notification_stmt->execute();
                    }
                    $notification_stmt->close();
                }
                // --- End Add Notification ---
            }
        } else {
            // Invalid password
            $error_message = "Invalid email or password.";
        }
    } else {
        // No user found with that email
        $error_message = "Invalid email or password.";
    }

    $stmt->close();
    
}

$conn->close();

?>

<!-- Login Form HTML below (moved from login.html) -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link rel="stylesheet" href="process_complaint.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="wrapper">
    <form action="login.php" method="POST">
      <h1>Login</h1>
      <!-- Error message display -->
      <?php if (!empty($error_message)): ?>
        <div style="color: red; text-align: center; margin-bottom: 1rem;">
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      <!-- Registration status message display -->
      <?php if (!empty($inactive_message)): ?>
          <div style="color: orange; text-align: center; margin-bottom: 1rem;">
              <?php echo htmlspecialchars($inactive_message); ?>
          </div>
      <?php endif; ?>
      <div class="input-box">
        <input type="text" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>" />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required />
        <i class='bx bxs-lock-alt'></i>
      </div>
      <div class="remember-forgot">
        <label><input type="checkbox" /> Remember me</label>
      </div>
      <button type="submit" class="btn">Login</button>
      <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register</a></p>
      </div>
    </form>
  </div>
</body>
</html>
