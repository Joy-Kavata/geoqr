<?php
// ===== SET TIMEZONE FIRST =====
date_default_timezone_set('Africa/Nairobi');

$password = "";
$username = "root";
$database = "geoqr";
//$port = "3306";
$host = "localhost";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ===== SET MySQL TIMEZONE =====
$conn->query("SET time_zone = '+03:00'");
?>