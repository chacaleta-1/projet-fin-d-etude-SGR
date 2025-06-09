<?php
require_once 'db_connect.php';

// SQL commands to add new columns
$sql = "ALTER TABLE complaints
        ADD COLUMN admin_feedback TEXT,
        ADD COLUMN feedback_date DATETIME";

try {
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added feedback columns to complaints table";
    } else {
        echo "Error adding columns: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 