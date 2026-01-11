<?php


$host = "sql105.infinityfree.com";   
$user = "if0_40311979";        
$pass = "zbRJYso8EuTcmF";           
$db   = "if0_40311979_admin_db"; 

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
// If no error, connection is OK
?>
