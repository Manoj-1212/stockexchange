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
if($_SESSION['role'] == 'admin') {
$sql = "select * from users where role != 'admin' ";
} else {
$sql = "select * from users where role = 'user' and parent_id = ".$_SESSION['user_id'];
}

$users = mysqli_query($conn, $sql);

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
    <link href="assets/vendor/datatables/datatables.min.css" rel="stylesheet">
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
                        <h3>Trading Clients</h3>
                    </div>
                    <div class="row">
                        
                        <div class="col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <form class="needs-validation" novalidate accept-charset="utf-8">
                                        <div class="row g-3">
                                            <div class="mb-3 col-md-3">
                                                <label for="email" class="form-label">ID</label>
                                                <input type="text" class="form-control" name="email" placeholder="ID" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="password" class="form-label">Name</label>
                                                <input type="text" class="form-control" name="password" placeholder="Name" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="password" class="form-label">UserName</label>
                                                <input type="text" class="form-control" name="password" placeholder="UserName" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="password" class="form-label">Admin</label>
                                                <input type="text" class="form-control" name="password" placeholder="Admin" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-secondary mb-2"> Reset</button>
                                                <button type="submit" class="btn btn-primary mb-2">Search</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-title"><a href="add_user.php" class="btn btn-sm btn-outline-primary float-end"><i class="fas fa-user-shield"></i> Add</a></p>
                                    <table class="table table-hover" id="dataTables-example" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>User Name</th>
                                                <th>Ledger Balance</th>
                                                <th>Gross PL</th>
                                                <th>Brokerage</th>
                                                <th>Net PL</th>
                                                <th>Broker</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($users)) { 
                                                $sql = "select name from users where id = ".$row['parent_id'];
$parent = mysqli_query($conn, $sql);
$parent = mysqli_fetch_assoc($parent);

$sql = "select sum(brokerage) as brokerage from brokerage where user_id = ".$row['id'];
$brokerage = mysqli_query($conn, $sql);
$brokerage = mysqli_fetch_assoc($brokerage);

$sql = "select sum(profit) as profit, sum(actual_profit) as actual_profit from profit_loss where user_id = ".$row['id'];
$profit_loss = mysqli_query($conn, $sql);
$profit_loss = mysqli_fetch_assoc($profit_loss);

                                            ?>
                                            <tr>
                                                <td width="10%"><i class="fas fa-circle" <?php if($row['status'] == 1) { ?> style="color:forestgreen" <?php }  else { ?> style="color:gray" <?php } ?> ></i> <?php echo $row['name']; ?></td>
                                                <td><?php echo $row['email']; ?></td>
                                                <td><?php echo $row['fund_balance']; ?></td>
                                                <td><?php echo round($profit_loss['profit'],2); ?></td>
                                                <td><?php echo round($brokerage['brokerage'],2); ?></td>
                                                <td><?php echo round($profit_loss['actual_profit'],2); ?></td>
                                                <td><?php echo $parent['name']; ?></td>
                                                <td>
                                                    <?php if($row['role'] == 'broker') { ?>
                                                        <a href="edit_broker.php?user_id=<?php echo $row['id']; ?>" class="btn btn-outline-info btn-rounded"><i class="fas fa-pen"></i></a>
                                                    <?php } else { ?>
                                                    <a href="edit_user.php?user_id=<?php echo $row['id']; ?>" class="btn btn-outline-info btn-rounded"><i class="fas fa-pen"></i></a>
                                                    <?php } ?>
                                            <a href="deactivate_user.php?user_id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-rounded"><i class="fas fa-user-slash"></i></a>
                                            <?php if($_SESSION['role'] == 'admin') { ?>
                                            <a href="add_fund.php?user_id=<?php echo $row['id']; ?>" class="btn btn-outline-green btn-rounded"><i class="fas fa-rupee-sign"></i></a>
                                            <?php } ?>
                                            </td>
                                            </tr>
                                           <?php } ?> 

                                        </tbody>
                                    </table>
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
    <script src="assets/vendor/datatables/datatables.min.js"></script>
    <script src="assets/js/initiate-datatables.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>