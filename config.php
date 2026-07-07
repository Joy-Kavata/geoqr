<?php
date_default_timezone_set('Africa/Nairobi');

$host = 'sql105.infinityfree.com';
$username = 'if0_42295230';
$password = 'BuNOlbXj4mCS6';
$database = 'if0_42295230_geoqr';
$port = '3306';

$conn = mysqli_connect($host, $username, $password, $database, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$conn->query("SET time_zone = '+03:00'");
?>