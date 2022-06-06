<?php
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$sql = "update users set nfo_leverage = ".$_POST['nfo_leverage'].", nfo_brokerage = ".$_POST['nfo_brokerage'].", nfo_holding = ".$_POST['nfo_holding'].", mcx_leverage = ".$_POST['mcx_leverage'].", mcx_brokerage = ".$_POST['mcx_brokerage'].", mcx_holding = ".$_POST['mcx_holding']." where id = ".$_POST['user_id'];

mysqli_query($conn, $sql);

$error = mysqli_error($conn);
echo json_encode([
            'success' => ($error)? false : true,
            'message' => ($error)? $error : "Updated Successfully !!" ,
        ]);

?>