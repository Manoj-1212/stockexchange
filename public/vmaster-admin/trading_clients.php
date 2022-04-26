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
                                        <div class="row g-3">
                                            <div class="mb-3 col-md-3">
                                                <label for="state" class="form-label">Account status</label>
                                                <select name="state" class="form-select" required>
                                                    <option value="1" selected>Active</option>
                                                    <option value="2">InActive</option>
                                                </select>
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
                                                <th>Credit Limit</th>
                                                <th>Ledger Balance</th>
                                                <th>Gross PL</th>
                                                <th>Brokerage</th>
                                                <th>Net PL</th>
                                                <th>Admin</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td width="10%"><i class="fas fa-circle" style="color:forestgreen"></i> Manoj</td>
                                                <td>100</td>
                                                <td>234</td>
                                                <td>787</td>
                                                <td>234</td>
                                                <td>251</td>
                                                <td>787</td>
                                                <td>MANAGER23</td>
                                                <td><a href="edit_user.php" class="btn btn-outline-info btn-rounded"><i class="fas fa-pen"></i></a>
                                            <a href="remove_user.php" class="btn btn-outline-danger btn-rounded"><i class="fas fa-user-slash"></i></a></td>
                                            </tr>
                                            <tr>
                                                <td width="10%"><i class="fas fa-circle" style="color:forestgreen"></i>Antony</td>
                                                <td>233</td>
                                                <td>345</td>
                                                <td>676</td>
                                                <td>234</td>
                                                <td>251</td>
                                                <td>787</td>
                                                <td>MANAGER27</td>
                                                <td><a href="edit_broker.php" class="btn btn-outline-info btn-rounded"><i class="fas fa-pen"></i></a>
                                            <a href="remove_user.php" class="btn btn-outline-danger btn-rounded"><i class="fas fa-user-slash"></i></a></td>
                                            </tr>
                                            <tr>
                                                <td width="10%"><i class="fas fa-circle" style="color:forestgreen"></i>Arjun</td>
                                                <td>456</td>
                                                <td>532</td>
                                                <td>567</td>
                                                <td>234</td>
                                                <td>251</td>
                                                <td>787</td>
                                                <td>MANAGER21</td>
                                                <td><a href="edit_user.php" class="btn btn-outline-info btn-rounded"><i class="fas fa-pen"></i></a>
                                            <a href="remove_user.php" class="btn btn-outline-danger btn-rounded"><i class="fas fa-user-slash"></i></a></td>
                                            </tr>
                                            <tr>
                                                <td width="10%"><i class="fas fa-circle" style="color:forestgreen"></i>Vishnu</td>
                                                <td>864</td>
                                                <td>809</td>
                                                <td>456</td>
                                                <td>234</td>
                                                <td>251</td>
                                                <td>787</td>
                                                <td>MANAGER25</td>
                                                <td><a href="edit_broker.php" class="btn btn-outline-info btn-rounded"><i class="fas fa-pen"></i></a>
                                            <a href="remove_user.php" class="btn btn-outline-danger btn-rounded"><i class="fas fa-user-slash"></i></a></td>
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