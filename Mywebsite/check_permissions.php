<?php
// Function to check if a directory is writable
function checkDirectory($path) {
    if (!file_exists($path)) {
        return "Directory does not exist: $path";
    }
    if (!is_writable($path)) {
        return "Directory is not writable: $path";
    }
    return "Directory is OK: $path";
}

// Function to check if a file is readable
function checkFile($path) {
    if (!file_exists($path)) {
        return "File does not exist: $path";
    }
    if (!is_readable($path)) {
        return "File is not readable: $path";
    }
    return "File is OK: $path";
}

// Check important directories
$directories = [
    '.',  // Current directory
    '../uploads',  // If you have an uploads directory
];

// Check important files
$files = [
    'db_connect.php',
    'login.php',
    'register.php',
    'admin_dashboard.php',
    'client_dashboard.php',
    'dashboard.css'
];

echo "<h2>Directory Permissions Check:</h2>";
foreach ($directories as $dir) {
    echo checkDirectory($dir) . "<br>";
}

echo "<h2>File Permissions Check:</h2>";
foreach ($files as $file) {
    echo checkFile($file) . "<br>";
}

// Check PHP version and extensions
echo "<h2>PHP Environment Check:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Extension: " . (extension_loaded('mysqli') ? 'Loaded' : 'Not Loaded') . "<br>";
echo "GD Extension: " . (extension_loaded('gd') ? 'Loaded' : 'Not Loaded') . "<br>";

// Check database connection
echo "<h2>Database Connection Check:</h2>";
include 'db_connect.php';
if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error;
} else {
    echo "Database connection successful!";
}
?> 