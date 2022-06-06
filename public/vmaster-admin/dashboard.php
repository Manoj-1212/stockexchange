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
    $condition1 = " and user_id in (".$users['user'].")";
    $condition2 = " and broker_id = ".$_SESSION['user_id'];
} else {
    $condition1 = " ";
    $condition2 = " ";
}


$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 1 and status = 2 and exchange = 1 $condition1";
$buy_turnover_nfo = mysqli_query($conn, $sql);
$buy_turnover_nfo = mysqli_fetch_assoc($buy_turnover_nfo);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 1 and status = 2 and exchange = 2 $condition1";
$buy_turnover_mcx = mysqli_query($conn, $sql);
$buy_turnover_mcx = mysqli_fetch_assoc($buy_turnover_mcx);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 2 and status = 2 and exchange = 1 $condition1";
$sell_turnover_nfo = mysqli_query($conn, $sql);
$sell_turnover_nfo = mysqli_fetch_assoc($sell_turnover_nfo);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 2 and status = 2 and exchange = 2 $condition1";
$sell_turnover_mcx = mysqli_query($conn, $sql);
$sell_turnover_mcx = mysqli_fetch_assoc($sell_turnover_mcx);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE status = 2 and exchange = 1 $condition1";
$total_turnover_nfo = mysqli_query($conn, $sql);
$total_turnover_nfo = mysqli_fetch_assoc($total_turnover_nfo);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE status = 2 and exchange = 2 $condition1";
$total_turnover_mcx = mysqli_query($conn, $sql);
$total_turnover_mcx = mysqli_fetch_assoc($total_turnover_mcx);

$sql = "SELECT SUM(brokerage) as brokerage FROM `brokerage` WHERE exchange = 1 $condition2";
$brokerage_nfo = mysqli_query($conn, $sql);
$brokerage_nfo = mysqli_fetch_assoc($brokerage_nfo);

$sql = "SELECT SUM(brokerage) as brokerage FROM `brokerage` WHERE exchange = 2 $condition2";
$brokerage_mcx = mysqli_query($conn, $sql);
$brokerage_mcx = mysqli_fetch_assoc($brokerage_mcx);

$sql = "SELECT SUM(actual_profit) as actual_profit FROM `profit_loss` WHERE exchange = 1 $condition2";
$profit_nfo = mysqli_query($conn, $sql);
$profit_nfo = mysqli_fetch_assoc($profit_nfo);

$sql = "SELECT SUM(actual_profit) as actual_profit FROM `profit_loss` WHERE exchange = 2 $condition2";
$profit_mcx = mysqli_query($conn, $sql);
$profit_mcx = mysqli_fetch_assoc($profit_mcx);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 1 and status = 1 and exchange = 1 $condition1";
$active_buy_nfo = mysqli_query($conn, $sql);
$active_buy_nfo = mysqli_fetch_assoc($active_buy_nfo);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 1 and status = 1 and exchange = 2 $condition1";
$active_buy_mcx = mysqli_query($conn, $sql);
$active_buy_mcx = mysqli_fetch_assoc($active_buy_mcx);

$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 2 and status = 0 and exchange = 1 $condition1";
$active_sell_nfo= mysqli_query($conn, $sql);
$active_sell_nfo = mysqli_fetch_assoc($active_sell_nfo);


$sql = "SELECT SUM(total_amount) as total_amount FROM `order_checkout` WHERE action = 2 and status = 0 and exchange = 2 $condition1";
$active_sell_mcx = mysqli_query($conn, $sql);
$active_sell_mcx = mysqli_fetch_assoc($active_sell_mcx);

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard | Stock</title>
    <link href="assets/vendor/fontawesome/css/fontawesome.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/solid.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome/css/brands.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/master.css" rel="stylesheet">
    <link href="assets/vendor/flagiconcss/css/flag-icon.min.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <?php include_once("menu.php"); ?>
        <div id="body" class="active">
            <!-- navbar navigation component -->
            <?php include_once("settings.php"); ?>
            <!-- end of navbar navigation -->
            <div class="content">
                <div class="container">
                    <?php if($_SESSION['role'] == 'admin') { ?>
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-header">Live M2M under : CHAND01</div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">User ID</th>
                                                    <th scope="col">Ledger Balance</th>
                                                    <th scope="col">M2M</th>
                                                    <th scope="col">Active profit/loss</th>
                                                    <th scope="col">Active Trades</th>
                                                    <th scope="col">Margin Used</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row"><i class="fas fa-eye"></i></th>
                                                    <td>Mark</td>
                                                    <td>Otto</td>
                                                    <td>@mdo</td>
                                                    <td>Larry the Bird</td>
                                                    <td>Gogles</td>
                                                    <td>@twitter</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><i class="fas fa-eye"></i></th></th>
                                                    <td>Jacob</td>
                                                    <td>Thornton</td>
                                                    <td>@fat</td>
                                                    <td>Larry the Bird</td>
                                                    <td>Gogles</td>
                                                    <td>@twitter</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><i class="fas fa-eye"></i></th></th>
                                                    <td>Larry the Bird</td>
                                                    <td>Gogles</td>
                                                    <td>@twitter</td>
                                                    <td>Larry the Bird</td>
                                                    <td>Gogles</td>
                                                    <td>@twitter</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Buy Turnover
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo $buy_turnover_mcx['total_amount'] ; ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo $buy_turnover_nfo['total_amount'] ; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Sell Turnover
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo $sell_turnover_mcx['total_amount'] ; ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo $sell_turnover_nfo['total_amount'] ; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Total Turnover
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo $total_turnover_mcx['total_amount'] ; ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo $total_turnover_nfo['total_amount'] ; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Active Users
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number">0</span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Profit/Loss
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo round($profit_mcx['actual_profit'],2); ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo round($profit_nfo['actual_profit'],2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Brokerage
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo round($brokerage_mcx['brokerage'],2); ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo round($brokerage_nfo['brokerage'],2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Active Buy
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo ($active_buy_mcx['total_amount'])?$active_buy_mcx['total_amount']:0; ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo ($active_buy_nfo['total_amount'])?$active_buy_nfo['total_amount']:0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-4 mt-4">
                            <div class="card">
                                <div class="content">
                                    <div class="footer">
                                        
                                        <div class="stats">
                                            Active Sell
                                        </div>
                                        <hr />
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 text-end">
                                            <div class="detail">
                                                <p class="detail-subtitle">Mcx</p>
                                                <span class="number"><?php echo ($active_sell_mcx['total_amount'])?$active_sell_mcx['total_amount']:0; ?></span>
                                            </div>
                                            <div class="detail mt-4">
                                                <p class="detail-subtitle">Equity</p>
                                                <span class="number"><?php echo ($active_sell_nfo['total_amount'])?$active_sell_nfo['total_amount']:0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        

                    </div>
                    <?php if($_SESSION['role'] == 'admin') { ?>
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-3">
                            <div class="card">
                                <div class="content">
                                    <div class="row">
                                        <div class="dfd text-center">
                                            <h4 class="mb-0">Rs. 1,21,231 INR</h4>
                                            <p class="text-muted">In 1028 Transactions</p>
                                            <p class="text-muted">From Brokerage : 12,320 <br />From PL : 0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-3">
                            <div class="card">
                                <div class="content">
                                    <div class="row">
                                        <div class="dfd text-center">
                                            <h4 class="mb-0">Rs. 1,21,231 INR</h4>
                                            <p class="text-muted">In 1028 Transactions</p>
                                            <p class="text-muted">From Brokerage : 12,320 <br />From PL : 0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-3">
                            <div class="card">
                                <div class="content">
                                    <div class="row">
                                        <div class="dfd text-center">
                                            <h4 class="mb-0">Rs. 1,21,231 INR</h4>
                                            <p class="text-muted">In 1028 Transactions</p>
                                            <p class="text-muted">From Brokerage : 12,320 <br />From PL : 0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-3">
                            <div class="card">
                                <div class="content">
                                    <div class="row">
                                        <div class="dfd text-center">
                                            <h4 class="mb-0">Rs. 1,21,231 INR</h4>
                                            <p class="text-muted">In 1028 Transactions</p>
                                            <p class="text-muted">From Brokerage : 12,320 <br />From PL : 0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chartsjs/Chart.min.js"></script>
    <script src="assets/js/dashboard-charts.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>
