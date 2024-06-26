<?php
$host = 'localhost';
$db = '';
$user = '';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    file_put_contents('php://stderr', "Connection failed: " . $conn->connect_error . "\n");
    die("Connection failed: " . $conn->connect_error);
} else {
    file_put_contents('php://stderr', "Connected to database\n");
}