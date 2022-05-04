<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{$data['title']}}</title>
    <link href="{{ asset('/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/css/auth.css') }}" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <div class="auth-content">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <img class="brand" src="{{ url('/') }}/assets/img/bootstraper-logo.png" alt="bootstraper logo">
                    </div>
                    <h6 class="mb-4 text-muted">Login to your account</h6>
                    <h6 class="mb-4 text-muted" style="color:red!important;">{{ $message ?? '' }}</h6>
                    <form action="{{ url("api/login_admin") }}" method="POST">
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email adress</label>
                            <input type="email" required class="form-control" name="email" placeholder="Enter Email" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" required name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary shadow-2 mb-4">Login</button>
                    </form>
                    <!--<p class="mb-2 text-muted">Forgot password? <a href="forgot-password.html">Reset</a></p>-->
                </div>
            </div>
        </div>
    </div>
    <script src="{{ url('/') }}/assets/vendor/jquery/jquery.min.js"></script>
    <script src="{{ url('/') }}/assets/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>