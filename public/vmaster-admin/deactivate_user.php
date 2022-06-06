<?php
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$sql = "update users set status = 0 where id = ".$_GET['user_id'];

mysqli_query($conn, $sql);

header('location: trading_clients.php');

?>