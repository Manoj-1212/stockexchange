<?php
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "update users set name = '".$_POST['name']."', email = '".$_POST['email']."', phone_number = '".$_POST['phone']."', address = '".$_POST['address']."', parent_id = ".$_POST['broker']." where id = ".$_POST['user_id'];

mysqli_query($conn, $sql);

$error = mysqli_error($conn);
echo json_encode([
            'success' => ($error)? false : true,
            'message' => ($error)? $error : "Successfully Added !!" ,
        ]);

?>