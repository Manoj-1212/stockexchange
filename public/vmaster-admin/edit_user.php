<?php 
session_start();
if(empty($_SESSION)){
    header('location: index.php');
}
include_once('database.php');
// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "select name,id from users where role = 'broker' and status = 1  order by id desc";

$brokers = mysqli_query($conn, $sql);

$sql = "select * from users where role = 'user' and id = ".$_GET['user_id'];

$users = mysqli_query($conn, $sql);
$users = mysqli_fetch_assoc($users);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Trading Clients | Stock</title>
    <link href="assets/vendor/fontawesome/css/fontawesome.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/solid.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/brands.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/master.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <!-- sidebar navigation component -->
        <?php include_once("menu.php"); ?>
        <!-- end of sidebar component -->
        <div id="body" class="active">
            <!-- navbar navigation component -->
            <?php include_once("settings.php"); ?>
            <!-- end of navbar navigation -->
            <div class="content">
                <div class="container">
                    <div class="page-title">
                        <h3>Edit Users</h3>
                    </div>
                    <div class="box box-primary">
                        <div class="box-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="system-tab" data-bs-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">Users</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade  active show" id="system" role="tabpanel" aria-labelledby="system-tab">
                                    <div class="col-md-6">
                                        <p class="text-muted" id="usersuccess"></p>
                                        <form method="post" action="" id="userForm">
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Name</label>
                                            <input type="text" value="<?php echo $users['name']; ?>" required name="name" id="name" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">User Name/Email</label>
                                            <input type="email" value="<?php echo $users['email']; ?>" required name="email" id="email" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Phone Number</label>
                                            <input type="text" value="<?php echo $users['phone_number']; ?>" required name="phone" id="phone" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Address</label>
                                            <input type="text" value="<?php echo $users['address']; ?>" required name="address" id="address" class="form-control">
                                        </div>
                                        <?php if($_SESSION['role'] == 'admin') { ?>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Broker</label>
                                            <select name="broker" id="broker" required class="form-select">
                                                <option value="">Select your Broker</option>
                                                <?php while($broker = mysqli_fetch_assoc($brokers )) { ?>
                                                    <option <?php if($broker['id'] == $users['parent_id']) { ?> selected <?php } ?> value="<?php echo $broker['id']; ?>"><?php echo $broker['name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } else { ?>
                                        <input type="hidden" name="broker" id="broker" class="form-control" value="<?php echo $_SESSION['user_id']; ?>">
                                    <?php } ?>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Wallet Balance</label>
                                            <input type="text" readonly value="<?php echo $users['fund_balance']; ?>" required class="form-control">
                                            <input type="hidden" name="user_id" id="user_id" class="form-control" value="<?php echo $users['id']; ?>">
                                        </div>

                                        <div class="mb-3 text-end">
                                            <a href="trading_clients.php"><span class="btn btn-secondary"> Cancel</span></a>
                                            <button class="btn btn-success" id="userButton" type="submit"><i class="fas fa-check"></i> Save</button>
                                        </div>
                                        </form>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    $(document).ready(function(){
        // click on button submit
        $("#userForm").on('submit', function(e){
            e.preventDefault();
            $.ajax({
                url: 'update_user.php', // url where to submit the request
                type : "POST", // type of action POST || GET
                dataType : 'json', // data type
                data : $("#userForm").serialize(),
                success : function(result) {
                    // you can see the result from the console
                    // tab of the developer tools
                    if(result.error){
                        console.log(result.error.email[0]);
                        $("#usersuccess").html(result.error.email[0]);
                    }
                    if(result.success){
                        console.log(result.message);
                        $("#usersuccess").html(result.message);
                    }
                },
                error: function(xhr, resp, text) {
                    console.log(xhr, resp, text);
                }
            })
        });

    });

</script>
</body>

</html>