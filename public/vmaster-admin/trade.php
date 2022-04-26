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
                                        <div class="row g-3">
                                            <div class="mb-3 col-md-3">
                                                <label for="city" class="form-label">Buy Rate</label>
                                                <input type="text" class="form-control" name="city" placeholder="Buy Rate" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="zip" class="form-label">Sell Rate</label>
                                                <input type="text" class="form-control" name="zip" placeholder="Sell Rate" required>
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="zip" class="form-label">Lots / Unit</label>
                                                <input type="text" class="form-control" name="zip" placeholder="Lots / Unit" required>
                                                
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
                                                <th>ID</th>
                                                <th>Script</th>
                                                <th>Lots</th>
                                                <th>Buy Rate</th>
                                                <th>Sell Rate</th>
                                                <th>Segment</th>
                                                <th>User ID</th>
                                                <th>Bought At</th>
                                                <th>Sold At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>2523523</td>
                                                <td>HDFC</td>
                                                <td>100</td>
                                                <td>234</td>
                                                <td>787</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>2523523</td>
                                                <td>TCS</td>
                                                <td>233</td>
                                                <td>345</td>
                                                <td>676</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>2523523</td>
                                                <td>TATA ELEXY</td>
                                                <td>456</td>
                                                <td>532</td>
                                                <td>567</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>2523523</td>
                                                <td>UST</td>
                                                <td>864</td>
                                                <td>809</td>
                                                <td>456</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>2523523</td>
                                                <td>TATA CHEMICALS</td>
                                                <td>355</td>
                                                <td>567</td>
                                                <td>345</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>2523523</td>
                                                <td>ADANI PORT</td>
                                                <td>134</td>
                                                <td>353</td>
                                                <td>234</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
                                            </tr>
                                            <tr>
                                                <td>2523523</td>
                                                <td>GRAPHITE</td>
                                                <td>764</td>
                                                <td>239</td>
                                                <td>123</td>
                                                <td>234</td>
                                                <td>CHAN23:sd</td>
                                                <td>787</td>
                                                <td>787</td>
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