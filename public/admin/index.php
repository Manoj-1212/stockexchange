<?php 
session_start();
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = '';
if($_POST['submit']){
    $sql = "select * from users where email = '".$_POST['email']."' and password =  '".md5($_POST['password'])."' and role in ('admin','broker') and status = 1 ";

    $users = mysqli_query($conn, $sql);
    $count = mysqli_num_rows($users);
    if($count == 0){
        $message = 'Email/Password incorrect !!';
    } else {
        $users = mysqli_fetch_assoc($users); 
        $_SESSION['name']= $users['name'];
        $_SESSION['email']= $users['email'];
        $_SESSION['role']= $users['role'];
        $_SESSION['user_id']= $users['id'];
        header('location: dashboard.php');
    }

}

?>
<!doctype html>
<!-- 
* Bootstrap Simple Admin Template
* Version: 2.1
* Author: Alexis Luna
* Website: https://github.com/alexis-luna/bootstrap-simple-admin-template
-->
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login | Bootstrap Simple Admin Template</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <div class="auth-content">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <img class="brand" src="assets/img/bootstraper-logo.png" alt="bootstraper logo">
                    </div>
                    <h6 class="mb-4 text-muted">Login to your account</h6>
                    <p class="text-center" style="color:red"><?php echo $message; ?></p>
                    <form action="" method="POST">
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email adress</label>
                            <input type="text" name="email" required class="form-control" placeholder="Enter Email" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" required class="form-control" placeholder="Password" required>
                        </div>
                        <input type="submit" name="submit" class="btn btn-primary shadow-2 mb-4" value="Login">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>