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

$sql = "select * from instruments where expiry > '".date('Y-m-d')."' order by expiry asc";
$instruments = mysqli_query($conn, $sql);

$sql = "select GROUP_CONCAT(instrument_token) as tokens from instruments where expiry > '".date('Y-m-d')."' order by expiry asc";
$tokens = mysqli_query($conn, $sql);
$tokens = mysqli_fetch_assoc($tokens);


$sql = "select * from kite_setting ";
$kite_setting = mysqli_query($conn, $sql);
$kite_setting = mysqli_fetch_assoc($kite_setting);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Market Watch | Stock</title>
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
                        <h3>Market Watch</h3>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-title"></p>
                                    <table class="table table-hover" id="dataTables-market" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Script</th>
                                                <th>LTP</th>
                                                <th>Change</th>
                                                <th>High</th>
                                                <th>Low</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = mysqli_fetch_assoc($instruments)) { ?>
                                            <tr>
                                                <td><?php echo $row['trading_symbol']; ?></td>
                                                <td id="<?php echo $row['instrument_token']."-LTP"; ?>">0</td>
                                                <td id="<?php echo $row['instrument_token']."-change"; ?>">0%</td>
                                                <td id="<?php echo $row['instrument_token']."-high"; ?>">0</td>
                                                <td id="<?php echo $row['instrument_token']."-low"; ?>">0</td>
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
    <script src="ticker.js"></script>
    <!-- https://kite.zerodha.com/connect/login?v=3&api_key=bxgemb4liqbi58ki -->
    <script>
    var ticker = new KiteTicker({api_key: "bxgemb4liqbi58ki", access_token: "<?php echo $kite_setting['access_token']; ?>"});

    ticker.autoReconnect(true, 10, 5)
    ticker.connect();
    ticker.on("ticks", onTicks);
    ticker.on("connect", subscribe);

    function onTicks(ticks) {
        for(var i=0;i<Object.keys(ticks).length;i++){
            console.log(ticks[i].instrument_token,ticks[i]);
            $("#"+ticks[i].instrument_token+"-LTP").html(ticks[i].last_price);
            $("#"+ticks[i].instrument_token+"-change").html((ticks[i].change)?(ticks[i].change).toFixed(2)+"%" : '0%');
            $("#"+ticks[i].instrument_token+"-high").html(ticks[i].ohlc.high);
            $("#"+ticks[i].instrument_token+"-low").html(ticks[i].ohlc.low);
        }
        
        
    }

    function subscribe() {
        var items = <?php echo "[".$tokens['tokens']."]"; ?>;
        ticker.subscribe(items);
        ticker.setMode(ticker.modeFull, items);
    }
    </script>
</body>

</html>