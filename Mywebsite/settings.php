<?php
session_start();

// Check if user is logged in (admin or client might have settings)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

$message = '';
$message_type = ''; // 'success' or 'error'

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current user data to verify password
    $sql = "SELECT password FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($current_password, $user['password'])) {
        $message = "Incorrect current password.";
        $message_type = 'error';
    } else if ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
        $message_type = 'error';
    } else if (empty($first_name)) {
         $message = "First name cannot be empty.";
         $message_type = 'error';
    } else {
        // Passwords match and names are not empty, proceed with update
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update_sql = "UPDATE users SET first_name = ?, password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $first_name, $hashed_password, $user_id);

        if ($update_stmt->execute()) {
            $message = "Profile updated successfully.";
            $message_type = 'success';
            // Update session variable if first name changed
            $_SESSION['user_name'] = $first_name;
        } else {
            $message = "Failed to update profile. Please try again.";
            $message_type = 'error';
            // In a real application, log $update_stmt->error for debugging
        }
        $update_stmt->close();
    }

     // Re-fetch user data to display updated first name if needed (though session is updated)
     // This is more relevant if you had other fields not updated via session
     $sql = "SELECT first_name FROM users WHERE id = ? LIMIT 1";
     $stmt = $conn->prepare($sql);
     $stmt->bind_param("i", $user_id);
     $stmt->execute();
     $result = $stmt->get_result();
     $user = $result->fetch_assoc();
     $stmt->close();
}

// You can fetch user specific settings here if you have a settings table
// For now, we'll just display the static HTML content

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <!-- Link to dashboard.css as well if sidebar is included -->
    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'client' || $_SESSION['user_role'] === 'admin')): ?>
        <link rel="stylesheet" href="dashboard.css">
    <?php endif; ?>
    <link rel="stylesheet" href="settings.css"> <!-- Link settings.css after dashboard.css -->
    <style>
    /* Styles to force sidebar positioning and main content area */
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        position: relative; /* Needed for absolute positioning of main content */
    }

    .sidebar {
        width: 240px; /* Keep sidebar width */
        height: 100vh; /* Keep sidebar height */
        position: fixed; /* Make sidebar fixed */
        top: 0;
        left: 0;
        /* Other sidebar styles from dashboard.css should apply */
        /* Ensure sidebar has a background color */
        background-color: #1e1e2f; /* Explicitly set background if needed */
        color: white;
        padding: 20px;
        box-sizing: border-box;
        overflow-y: auto; /* Add scrolling if sidebar content is too long */
    }

    .main {
        flex-grow: 1 !important; /* Force main content to take up remaining space */
        padding: 30px !important; /* Apply padding to main content */
        text-align: center; /* Center align content within main */
        display: block; /* Explicitly set display to block */
        /* Other main styles from dashboard.css should apply */
    }

    /* Styles for the form wrapper to contain and style the settings form */
    .main .settings-form {
         display: block; /* Ensure the form wrapper is a block element */
         margin-top: 30px;
         background-color: #fff;
         padding: 20px;
         border-radius: 8px;
         box-shadow: 0 0 10px rgba(0,0,0,0.05);
         max-width: 700px;
         width: 100%; /* Ensure it takes full width up to max-width */
         margin-left: auto;
         margin-right: auto;
     }

     .main .settings-form h3 {
         color: #1e1e2f;
         margin-bottom: 15px;
         margin-top: 25px;
     }

     .main .settings-form h3:first-child {
         margin-top: 0;
     }

     .main .settings-form .input-box {
         margin-bottom: 15px;
     }

     .main .settings-form label {
         display: block;
         margin-bottom: 5px;
         font-weight: bold;
         color: #555;
     }

     .main .settings-form input[type="text"],
     .main .settings-form input[type="password"] {
          width: 100%;
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
          box-sizing: border-box;
     }

     .main .settings-form button[type="submit"] {
          display: inline-block;
          width: auto;
          padding: 10px 20px;
          background-color: #00c6ff;
          color: white;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          transition: background-color 0.3s ease;
          margin-top: 10px;
     }

     .main .settings-form button[type="submit"]:hover {
         background-color: #0099cc;
     }

     /* Keep existing back link and message styles in settings.css or here */
     /* Since we linked settings.css after dashboard.css, styles in settings.css will apply */
     /* You might remove these from settings.css if you prefer to keep them all here */
    </style>
    <style>
    /* Styles for the new centering container */
    .settings-form-container {
        text-align: center; /* Center inline/inline-block children */
        /* Or use flexbox on the container */
        /* display: flex; */
        /* justify-content: center; */
    }

    /* Essential layout styles to ensure sidebar is fixed and main content fills space */
    /* Reinforcing styles from dashboard.css */
    body {
        display: flex !important; /* Force flexbox on body */
        margin: 0 !important;
        padding: 0 !important;
        min-height: 100vh !important; /* Ensure body takes full viewport height */
        box-sizing: border-box !important;
    }

    .sidebar {
        width: 240px !important; /* Force sidebar width */
        height: 100vh !important; /* Force sidebar height */
        position: sticky !important; /* Make sidebar sticky */
        top: 0 !important; /* Position sticky sidebar at the top */
        /* Other sidebar styles from dashboard.css should apply */
    }

    .main {
        flex-grow: 1 !important; /* Force main content to take up remaining space */
        padding: 30px !important; /* Apply padding to main content */
        text-align: center; /* Center align content within main */
        display: block; /* Explicitly set display to block */
        /* Other main styles from dashboard.css should apply */
    }

    /* Styles for the form wrapper to contain and style the settings form */
    .main .settings-form {
         margin-top: 30px;
         background-color: #fff;
         padding: 20px;
         border-radius: 8px;
         box-shadow: 0 0 10px rgba(0,0,0,0.05);
         max-width: 700px;
         margin: auto !important; /* Force auto margins for centering */
         display: block !important; /* Force display to block */
    }

    .main .settings-form h3 {
        color: #1e1e2f;
        margin-bottom: 15px;
        margin-top: 25px;
    }

    .main .settings-form h3:first-child {
        margin-top: 0;
    }

    .main .settings-form .input-box {
        margin-bottom: 15px;
    }

    .main .settings-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    .main .settings-form input[type="text"],
    .main .settings-form input[type="password"] {
         width: 100%;
         padding: 10px;
         border: 1px solid #ddd;
         border-radius: 4px;
         font-size: 14px;
         box-sizing: border-box;
    }

    .main .settings-form button[type="submit"] {
         display: inline-block;
         width: auto;
         padding: 10px 20px;
         background-color: #00c6ff;
         color: white;
         border: none;
         border-radius: 4px;
         cursor: pointer;
         transition: background-color 0.3s ease;
         margin-top: 10px;
    }

    .main .settings-form button[type="submit"]:hover {
        background-color: #0099cc;
    }

    /* Keep existing back link and message styles in settings.css or here */
    /* Since we linked settings.css after dashboard.css, styles in settings.css will apply */
    /* You might remove these from settings.css if you prefer to keep them all here */
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
        <!-- Include client sidebar if needed -->
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
              <li><a href="settings.php" class="active"><span class="las la-sliders-h"></span> Settings</a></li>
              <li><a href="complaint_history.php"><span class="las la-history"></span> History</a></li>
              <li><a href="print_complaints.php"><span class="las la-print"></span> Print List</a></li>
            </ul>
          </div>
    <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
         <!-- Include admin sidebar if needed -->
          <div class="sidebar">
            <div class="profile">
              <img src="img-2.png" alt="Admin" />
              <h3>Admin Dashboard</h3>
              <form method="post" action="logout.php">
                <button class="signout" type="submit">Sign Out</button>
              </form>
            </div>
            <ul>
              <li><a href="admin_dashboard.php"><span class="las la-comment-dots"></span> Manage Complaints</a></li>
              <!-- Add other admin links here if needed -->
            </ul>
          </div>
    <?php endif; ?>

    <div class="main">
        <h2>Settings</h2>
        <p>This is where you can change your account preferences and password.</p>

        <!-- Settings form will go here -->
        <div class="settings-form-container">
            <div class="settings-form" style="margin-top: 30px; background-color: #f9f9f9; padding: 20px; border-radius: 8px;">
                <h3>Update Profile Information</h3>
                 <?php if (isset($message)): ?>
                    <div style="color: <?php echo $message_type === 'success' ? 'green' : 'red'; ?>; text-align: center; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <form action="settings.php" method="POST">
                    <div class="input-box" style="margin-bottom: 15px;">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>

                    <h3 style="margin-top: 25px;">Change Password</h3>
                     <div class="input-box" style="margin-bottom: 15px;">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>

                    <div class="input-box" style="margin-bottom: 15px;">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>

                    <div class="input-box" style="margin-bottom: 20px;">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;"/>
                    </div>

                    <button type="submit" class="btn" style="padding: 10px 15px; background-color: #00c6ff; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
             <a href="client_dashboard.php" class="back-link">Back to Dashboard</a>
        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
             <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>
        <?php endif; ?>

      </div>
</body>
</html> 