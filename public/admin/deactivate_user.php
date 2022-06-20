<?php
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "select status from users where id = ".$_GET['user_id'];
$users = mysqli_query($conn, $sql);
$users = mysqli_fetch_assoc($users);
if($users['status'] == 1){
    $sql = "update users set status = 0 where id = ".$_GET['user_id'];
} else {
    $sql = "update users set status = 1 where id = ".$_GET['user_id'];
}

mysqli_query($conn, $sql);

header('location: trading_clients.php');

?>