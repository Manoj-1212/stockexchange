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

$sql = "select GROUP_CONCAT(id) as user from users where parent_id = ".$_SESSION['user_id'];
$users = mysqli_query($conn, $sql);
$users = mysqli_fetch_assoc($users);


if($_SESSION['role'] == 'broker'){
    $condition1 = " where user_id in (".$users['user'].")";
} else {
    $condition1 = " ";
    $condition2 = " ";
}

$sql = "select order_checkout.*,instruments.trading_symbol,users.name, (CASE order_checkout.status WHEN 1 THEN 'PENDING' WHEN 2 THEN 'CLOSED' WHEN 0 THEN 'ACTIVE' ELSE 'CANCELLED' END) as status from order_checkout join instruments on instruments.instrument_token = order_checkout.instrument_id join users on users.id = order_checkout.user_id $condition1 order by order_checkout.created_at desc";
$closed = mysqli_query($conn, $sql);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Trades | Stock</title>
    <link href="assets/vendor/fontawesome/css/fontawesome.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/solid.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/brands.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/datatables/datatables.min.css" rel="stylesheet">
    <link href="assets/css/master.css" rel="stylesheet">
    <link href="assets/vendor/airdatepicker/css/datepicker.min.css" rel="stylesheet">
    <link href="assets/vendor/mdtimepicker/mdtimepicker.min.css" rel="stylesheet">
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
                        <h3>Trades</h3>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <form>
                                        <br>
                                        <div class="mb-3">
                                            <input type="text" class="form-control datepicker-here" data-language="en" aria-describedby="datepicker" placeholder="Start Date">
                                        </div>
                                        <div class="mb-3">
                                            <input type="text" class="form-control datepicker-here" data-language="en" aria-describedby="datepicker" placeholder="End Date">
                                        </div>
                                        <div class="mb-3">
                                            <input type="submit" class="btn btn-primary" value="Export All Trades To Excel">
                                        </div>
                                    </form>
                        </div>
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <form class="needs-validation" novalidate accept-charset="utf-8">
                                        <div class="row g-3">
                                            <div class="mb-3 col-md-3">
                                                <label for="email" class="form-label">ID</label>
                                                <input type="text" class="form-control" name="email" placeholder="ID" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="password" class="form-label">Script</label>
                                                <input type="text" class="form-control" name="password" placeholder="Script" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="password" class="form-label">Segment</label>
                                                <input type="text" class="form-control" name="password" placeholder="Segment" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="password" class="form-label">User ID</label>
                                                <input type="text" class="form-control" name="password" placeholder="User ID" required>
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
                                    <p class="card-title"></p>
                                    <table class="table table-hover" id="dataTables-example" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Script</th>
                                                <th>Lots</th>
                                                <th>Buy Rate</th>
                                                <th>Sell Rate</th>
                                                <th>Segment</th>
                                                <th>User ID</th>
                                                <th>Bought At</th>
                                                <th>Sold At</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($closed)) { 
                                                if($row['action'] == 1) {
                                                    $buyrate = $row['amount'];
                                                    $sellrate = 0;
                                                    $boughtAt = $row['created_at'];
                                                    $sellAt = 'Not Set';
                                                } else {
                                                    $buyrate = 0;
                                                    $sellrate = $row['amount'];
                                                    $boughtAt = 'Not Set';
                                                    $sellAt = $row['created_at'];
                                                }
                                                ?>
                                            <tr>
                                                <td><?php echo $row['trading_symbol']; ?></td>
                                                <td><?php echo $row['qty']; ?></td>
                                                <td><?php echo $buyrate; ?></td>
                                                <td><?php echo $sellrate; ?></td>
                                                <td><?php echo ($row['exchange'] == 1)? 'Equity' : 'MCX'; ?></td>
                                                <td><?php echo $row['name']; ?></td>
                                                <td><?php echo $boughtAt; ?></td>
                                                <td><?php echo $sellAt; ?></td>
                                                <td><?php echo $row['status']; ?></td>
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
    <script src="assets/vendor/airdatepicker/js/datepicker.min.js"></script>
    <script src="assets/vendor/airdatepicker/js/i18n/datepicker.en.js"></script>
    <script src="assets/vendor/mdtimepicker/mdtimepicker.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script type="text/javascript">
    // Initiate time picker
    mdtimepicker('.timepicker', { format: 'h:mm tt', hourPadding: 'true' });
    </script>
</body>

</html>