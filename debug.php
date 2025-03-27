<?php
// Debug script to view server variables

echo "<h1>Server Information</h1>";

echo "<h2>PHP Version</h2>";
echo phpversion();

echo "<h2>Server Variables</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Extensions</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

echo "<h2>Database Connection Test</h2>";
require_once 'db_connection.php';

if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error;
} else {
    echo "Database connection successful!";
    
    // Test a simple query
    $result = $conn->query("SHOW TABLES");
    echo "<h3>Tables in Database:</h3>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
    // Test users table structure
    $result = $conn->query("DESCRIBE users");
    echo "<h3>Users Table Structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?> 