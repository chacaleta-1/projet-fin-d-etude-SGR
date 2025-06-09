<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';

// Handle user status toggle
if (isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all users
$sql = "SELECT id, first_name, last_name, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .user-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .status-inactive {
            background-color: #dc3545;
            color: white;
        }
        .toggle-btn {
            padding: 5px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .toggle-btn:hover {
            opacity: 0.9;
        }
        .activate-btn {
            background-color: #28a745;
            color: white;
        }
        .deactivate-btn {
            background-color: #dc3545;
            color: white;
        }
        .search-box {
            margin: 20px 0;
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .role-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
        }
        .role-select:hover {
            border-color: #999;
        }
        .last-login {
            font-size: 0.9em;
            color: #666;
        }
        .never-logged-in {
            color: #999;
            font-style: italic;
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
            <li><a href="manage_users.php" class="active"><span class="las la-users"></span> Manage Users</a></li>
            <li><a href="admin_settings.php"><span class="las la-cog"></span> Settings</a></li>
        </ul>
    </div>
    <div class="main">
        <div class="header">
            <h2>Manage Users</h2>
            <p>View and manage all registered users</p>
            <a href="admin_dashboard.php" class="back-to-dashboard">Back to Dashboard</a>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="search-container">
            <input type="text" id="searchInput" class="search-box" placeholder="Search users..." onkeyup="searchUsers()">
        </div>
        
        <div class="reclamations-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Last Login</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <form method="post" action="update_user_role.php" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <select name="new_role" onchange="confirmRoleChange(this)" class="role-select">
                                            <option value="client" <?php echo $row['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                                            <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <span class="user-status <?php echo $row['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td class="last-login">
                                    <?php 
                                    if ($row['last_login']) {
                                        echo htmlspecialchars($row['last_login']);
                                    } else {
                                        echo '<span class="never-logged-in">Never</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <?php if ($row['status'] === 'active'): ?>
                                            <input type="hidden" name="new_status" value="inactive">
                                            <button type="submit" name="toggle_status" class="toggle-btn deactivate-btn" onclick="return confirmStatusChange('deactivate')">Deactivate</button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" name="toggle_status" class="toggle-btn activate-btn" onclick="return confirmStatusChange('activate')">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function searchUsers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const tbody = document.getElementById('usersTableBody');
            const rows = tbody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell) {
                        const text = cell.textContent || cell.innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        function confirmRoleChange(selectElement) {
            const newRole = selectElement.value;
            const currentRole = selectElement.options[selectElement.selectedIndex].text;
            const userName = selectElement.closest('tr').querySelector('td:nth-child(2)').textContent;
            
            if (confirm(`Are you sure you want to change ${userName}'s role to ${currentRole}?`)) {
                selectElement.form.submit();
            } else {
                // Reset to previous value
                selectElement.value = selectElement.getAttribute('data-previous-value');
            }
        }

        function confirmStatusChange(action) {
            return confirm(`Are you sure you want to ${action} this user?`);
        }

        // Store previous role value when select is focused
        document.querySelectorAll('.role-select').forEach(select => {
            select.addEventListener('focus', function() {
                this.setAttribute('data-previous-value', this.value);
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?> 