<?php
require_once 'db_connection.php';

// SQL to add the missing columns
$alterTableSql = "
ALTER TABLE users
ADD COLUMN bio TEXT NULL,
ADD COLUMN profile_picture VARCHAR(255) NULL;
";

// Execute the SQL
if ($conn->multi_query($alterTableSql)) {
    echo "Columns added successfully\n";
} else {
    echo "Error adding columns: " . $conn->error . "\n";
}

$conn->close();
?> 