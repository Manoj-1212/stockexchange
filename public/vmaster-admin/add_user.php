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
                        <h3>Add Clients</h3>
                    </div>
                    <div class="box box-primary">
                        <div class="box-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">Brokers</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="system-tab" data-bs-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">Users</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade active show" id="general" role="tabpanel" aria-labelledby="general-tab">
                                    <div class="col-md-6">
                                        <p class="text-muted"></p>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Name</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">User Name</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Password</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Email</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Phone Number</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Address</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>

                                        <div class="mb-3 text-end">
                                            <a href="trading_clients.php"><button class="btn btn-secondary"> Cancel</button></a>
                                            <button class="btn btn-success" type="submit"><i class="fas fa-check"></i> Save</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                                    <div class="col-md-6">
                                        <p class="text-muted"></p>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Name</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">User Name</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Password</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Email</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Phone Number</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-description" class="form-label">Address</label>
                                            <input type="text" name="site_title" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site-title" class="form-label">Broker</label>
                                            <select name="timezone" class="form-select">
                                                <option value="">Select your Broker</option>
                                                <option value="1">Broker 1</option>
                                                <option value="2">Broker 2</option>
                                            </select>
                                        </div>

                                        <div class="mb-3 text-end">
                                            <a href="trading_clients.php"><button class="btn btn-secondary"> Cancel</button></a>
                                            <button class="btn btn-success" type="submit"><i class="fas fa-check"></i> Save</button>
                                        </div>
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
</body>

</html>