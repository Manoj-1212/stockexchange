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


$sql = "select * from users where id = ".$_SESSION['user_id'];
$users = mysqli_query($conn, $sql);
$users = mysqli_fetch_assoc($users);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Settings | Stock</title>
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
                        <h3>Settings</h3>
                    </div>
                    <div class="box box-primary">
                        <div class="box-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">Change Password</a>
                                </li>
                                <?php if($_SESSION['role'] == 'broker'){ ?>
                                <li class="nav-item">
                                    <a class="nav-link" id="system-tab" data-bs-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">Brokerage</a>
                                </li>
                                <?php } ?>
                                
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade active show" id="general" role="tabpanel" aria-labelledby="general-tab">
                                    <div class="col-md-6">
                                        <form method="post" action="" id="passwordForm">
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">New Password</label>
                                            <input type="text" required name="new_password" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Confirm Password</label>
                                            <input type="text" required name="confirm_password" class="form-control">
                                        </div>
                                        <div class="mb-3 text-end">
                                            <a href="dashboard.php"><span class="btn btn-secondary"> Cancel</span></a>
                                            <button class="btn btn-success" type="submit"><i class="fas fa-check"></i> Save</button>
                                        </div>
                                        </form>
                                    </div>
                                </div>
                                <?php if($_SESSION['role'] == 'broker'){ ?>
                                <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                                    <div class="col-md-6">
                                        <form method="post" action="" id="brokerageForm">
                                        <p class="text-muted" id="brokersuccess"></p>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Equity Leverage</label>
                                            <input type="text" value="<?php echo $users['nfo_leverage']; ?>" required name="nfo_leverage" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Equity Brokerage</label>
                                            <input type="text" value="<?php echo $users['nfo_brokerage']; ?>" required name="nfo_brokerage" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Equity Holding</label>
                                            <input type="text" value="<?php echo $users['nfo_holding']; ?>" required name="nfo_holding" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">MCX Leverage</label>
                                            <input type="text" value="<?php echo $users['mcx_leverage']; ?>" required name="mcx_leverage" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">MCX Brokerage</label>
                                            <input type="text" value="<?php echo $users['mcx_brokerage']; ?>" required name="mcx_brokerage" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">MCX Holding</label>
                                            <input type="text" value="<?php echo $users['mcx_holding']; ?>" required name="mcx_holding" class="form-control">
                                        </div>
                                        <div class="mb-3 text-end">
                                            <input type="hidden" value="<?php echo $_SESSION['user_id']; ?>" name="user_id" class="form-control">
                                            <a href="dashboard.php"><span class="btn btn-secondary"> Cancel</span></a>
                                            <button class="btn btn-success" type="submit"><i class="fas fa-check"></i> Save</button>
                                        </div>
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>
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
        $("#passwordForm").on('submit', function(e){
            e.preventDefault();
            // send ajax
            $.ajax({
                url: 'passwordForm.php', // url where to submit the request
                type : "POST", // type of action POST || GET
                dataType : 'json', // data type
                data : $("#passwordForm").serialize(), // post data || get data
                success : function(result) {
                    // you can see the result from the console
                    // tab of the developer tools
                    $("#passwordFormsuccess").html(result.message);
                },
                error: function(xhr, resp, text) {
                    console.log(xhr, resp, text);
                }
            })
        });

        $("#brokerageForm").on('submit', function(e){
            e.preventDefault();
            // send ajax
            $.ajax({
                url: 'brokerageForm.php', // url where to submit the request
                type : "POST", // type of action POST || GET
                dataType : 'json', // data type
                data : $("#brokerageForm").serialize(), // post data || get data
                success : function(result) {
                    // you can see the result from the console
                    // tab of the developer tools
                    $("#brokersuccess").html(result.message);
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