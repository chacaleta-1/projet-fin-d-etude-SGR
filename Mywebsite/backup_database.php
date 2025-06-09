<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all tables
$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Create backup file
$backup_file = 'database_backup_' . date("Y-m-d_H-i-s") . '.sql';
$handle = fopen($backup_file, 'w');

// Add DROP TABLE statements
foreach ($tables as $table) {
    fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
}

// Get table structures and data
foreach ($tables as $table) {
    // Get table structure
    $result = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $result->fetch_row();
    fwrite($handle, "\n" . $row[1] . ";\n\n");
    
    // Get table data
    $result = $conn->query("SELECT * FROM `$table`");
    while ($row = $result->fetch_assoc()) {
        $values = array_map(function($value) use ($conn) {
            return "'" . $conn->real_escape_string($value) . "'";
        }, $row);
        fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n");
    }
    fwrite($handle, "\n");
}

fclose($handle);
$conn->close();

echo "Database backup created successfully: $backup_file";
?> 