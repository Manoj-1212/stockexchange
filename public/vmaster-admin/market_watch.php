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
                                    <table class="table table-hover" id="dataTables-example" width="100%">
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
                                            <tr>
                                                <td>HDFC</td>
                                                <td>100</td>
                                                <td>5%</td>
                                                <td>234</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>TCS</td>
                                                <td>233</td>
                                                <td>2%</td>
                                                <td>345</td>
                                                <td>676</td>
                                            </tr>
                                            <tr>
                                                <td>TATA ELEXY</td>
                                                <td>456</td>
                                                <td>-.05%</td>
                                                <td>532</td>
                                                <td>567</td>
                                            </tr>
                                            <tr>
                                                <td>UST</td>
                                                <td>864</td>
                                                <td>8%</td>
                                                <td>809</td>
                                                <td>456</td>
                                            </tr>
                                            <tr>
                                                <td>TATA CHEMICALS</td>
                                                <td>355</td>
                                                <td>1.5%</td>
                                                <td>567</td>
                                                <td>345</td>
                                            </tr>
                                            <tr>
                                                <td>ADANI PORT</td>
                                                <td>134</td>
                                                <td>5.8%</td>
                                                <td>353</td>
                                                <td>234</td>
                                            </tr>
                                            <tr>
                                                <td>GRAPHITE</td>
                                                <td>764</td>
                                                <td>4.8%</td>
                                                <td>239</td>
                                                <td>123</td>
                                            </tr>
                                            
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