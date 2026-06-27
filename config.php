<?php
date_default_timezone_set('Africa/Nairobi');

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'geoqr';
$port = getenv('DB_PORT') ?: '3306';

$conn = mysqli_connect($host, $username, $password, $database, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$conn->query("SET time_zone = '+03:00'");
?>