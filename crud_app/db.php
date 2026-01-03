<?php
$servername = "localhost";
$username = "root";
$password = "";   // change if needed
$dbname = "classified_ads";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
