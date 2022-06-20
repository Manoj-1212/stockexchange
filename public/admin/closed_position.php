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



$sql = "select instrument_id from order_checkout where status = 2 group by instrument_id";
$closed = mysqli_query($conn, $sql);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Closed Position | Stock</title>
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
                        <h3>Active Positions</h3>
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
                                                <th>Lots Buy</th>
                                                <th>Lots Sell</th>
                                                <th>Avg Buy Rate</th>
                                                <th>Avg Sell Rate</th>
                                                <th>Profit/Loss</th>
                                                <th>Brokerage</th>
                                                <th>Net P/L</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($closed)){ 
                            $sql = "select * from instruments where instrument_token = ".$row['instrument_id'];
$instruments = mysqli_query($conn, $sql);  
$instruments = mysqli_fetch_assoc($instruments); 

$sql = "select sum(qty) as qty, sum(total_amount) as amount from order_checkout where status = 2 and action = 1 and instrument_id = ".$row['instrument_id'];
$order_checkout_buy = mysqli_query($conn, $sql);  
$order_checkout_buy = mysqli_fetch_assoc($order_checkout_buy);    

$sql = "select sum(qty) as qty, sum(total_amount) as amount from order_checkout where status = 2 and action = 2 and instrument_id = ".$row['instrument_id'];
$order_checkout_sell = mysqli_query($conn, $sql);  
$order_checkout_sell = mysqli_fetch_assoc($order_checkout_sell);  

$sql = "select sum(brokerage) as brokerage from brokerage where instrument_id = ".$row['instrument_id'];
$brokerage = mysqli_query($conn, $sql);  
$brokerage = mysqli_fetch_assoc($brokerage); 

$sql = "select sum(profit) as profit, sum(actual_profit) as actual_profit from profit_loss where instrument_id = ".$row['instrument_id'];
$profit_loss = mysqli_query($conn, $sql);  
$profit_loss = mysqli_fetch_assoc($profit_loss); 

                                            ?>
                                            <tr>
                                                <td><?php echo $instruments['trading_symbol']; ?></td>
                                                <td><?php echo $order_checkout_buy['qty']; ?></td>
                                                <td><?php echo $order_checkout_sell['qty']; ?></td>
                                                <td><?php echo round($order_checkout_buy['amount']/$order_checkout_buy['qty'],2); ?></td>
                                                <td><?php echo round($order_checkout_sell['amount']/$order_checkout_sell['qty'],2); ?></td>
                                                <td><?php echo $profit_loss['profit']; ?></td>
                                                <td><?php echo round($brokerage['brokerage'],4); ?></td>
                                                <td><?php echo round($profit_loss['actual_profit'],4); ?></td>
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