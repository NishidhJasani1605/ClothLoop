<?php
require_once 'db_connection.php';

// Get all users
$sql = "SELECT * FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Users in database:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Phone</th><th>User Type</th><th>Bio</th><th>Profile Picture</th></tr>";
    
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["username"] . "</td>";
        echo "<td>" . $row["email"] . "</td>";
        echo "<td>" . $row["phone"] . "</td>";
        echo "<td>" . $row["user_type"] . "</td>";
        echo "<td>" . ($row["bio"] ?? "NULL") . "</td>";
        echo "<td>" . ($row["profile_picture"] ?? "NULL") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}

$conn->close();
?> 