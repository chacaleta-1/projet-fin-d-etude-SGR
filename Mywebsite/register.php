<?php
require_once 'db_connect.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password_input)) {
        $message = "Please fill in all required fields.";
        $message_type = 'error';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $message = "Invalid email format.";
         $message_type = 'error';
    } else {
        // Check if email already exists
        $check_email_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();
        $check_email_stmt->close();

        if ($check_email_result->num_rows > 0) {
            $message = "Email address already registered.";
            $message_type = 'error';
        } else {
            // Hash the password
            $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

            // SQL Insert using prepared statement
            $insert_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, phone, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'client', 'active')");
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $phone, $email, $hashed_password);

            // Execute and respond
            if ($insert_stmt->execute()) {
                // Redirect to login page after successful registration
                header("Location: login.php?status=success");
                exit();
            } else {
                $message = "Registration failed. Please try again.";
                $message_type = 'error';
                // In a real application, log $insert_stmt->error for debugging
            }
            $insert_stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <link rel="stylesheet" href="process_complaint.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="wrapper">
    <form action="register.php" method="POST">
      <h1>Registration</h1>
      <?php if ($message): ?>
          <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
              <?php echo htmlspecialchars($message); ?>
          </div>
      <?php endif; ?>
      <div class="input-box">
        <input type="text" name="first_name" placeholder="First Name" required />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="text" name="last_name" placeholder="Last Name" required />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="text" name="phone" placeholder="Phone Number" />
        <i class='bx bxs-phone'></i>
      </div>
      <div class="input-box">
        <input type="email" name="email" placeholder="Email" required />
        <i class='bx bxs-envelope'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required />
        <i class='bx bxs-lock-alt'></i>
      </div>
      <button type="submit" class="btn">Register</button>
      <div class="register-link">
        <p>Already have an account? <a href="login.php">Login</a></p>
      </div>
    </form>
  </div>
</body>
</html>
