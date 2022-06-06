<?php
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$sql = "insert into users(name,email,phone_number,address,password,role,status,parent_id,created_at,updated_at) values('".$_POST['name']."','".$_POST['email']."','".$_POST['phone']."','".$_POST['address']."','".md5($_POST['password'])."','broker',1,0,'".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."')";

mysqli_query($conn, $sql);

$error = mysqli_error($conn);
echo json_encode([
            'success' => ($error)? false : true,
            'message' => ($error)? $error : "Successfully Added !!" ,
        ]);

?>