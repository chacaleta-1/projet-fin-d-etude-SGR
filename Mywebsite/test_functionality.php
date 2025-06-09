<?php
session_start();
include 'db_connect.php';

function testFunction($name, $result) {
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td>" . ($result ? "✅ PASS" : "❌ FAIL") . "</td>";
    echo "</tr>";
}

echo "<h2>Core Functionality Tests</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Result</th></tr>";

// Test 1: Database Connection
testFunction("Database Connection", $conn->connect_error === null);

// Test 2: Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
testFunction("Users Table Exists", $result->num_rows > 0);

// Test 3: Check if complaints table exists
$result = $conn->query("SHOW TABLES LIKE 'complaints'");
testFunction("Complaints Table Exists", $result->num_rows > 0);

// Test 4: Check if notifications table exists
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
testFunction("Notifications Table Exists", $result->num_rows > 0);

// Test 5: Check if required columns exist in users table
$result = $conn->query("SHOW COLUMNS FROM users");
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
$required_columns = ['id', 'name', 'email', 'password', 'user_type', 'status', 'created_at'];
$all_columns_exist = true;
foreach($required_columns as $column) {
    if(!in_array($column, $columns)) {
        $all_columns_exist = false;
        break;
    }
}
testFunction("Users Table Has Required Columns", $all_columns_exist);

// Test 6: Check if required columns exist in complaints table
$result = $conn->query("SHOW COLUMNS FROM complaints");
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
$required_columns = ['id', 'user_id', 'subject', 'description', 'status', 'created_at'];
$all_columns_exist = true;
foreach($required_columns as $column) {
    if(!in_array($column, $columns)) {
        $all_columns_exist = false;
        break;
    }
}
testFunction("Complaints Table Has Required Columns", $all_columns_exist);

// Test 7: Check if required files exist
$required_files = [
    'login.php',
    'register.php',
    'admin_dashboard.php',
    'client_dashboard.php',
    'dashboard.css',
    'db_connect.php'
];
$all_files_exist = true;
foreach($required_files as $file) {
    if(!file_exists($file)) {
        $all_files_exist = false;
        break;
    }
}
testFunction("Required Files Exist", $all_files_exist);

echo "</table>";

// Display any errors
if($conn->error) {
    echo "<h3>Database Errors:</h3>";
    echo $conn->error;
}
?> 