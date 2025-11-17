<?php

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "tipone_db";

$conn = mysqli_connect($localhost, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>


