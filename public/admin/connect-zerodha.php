<?php
    require_once __DIR__ . '/vendor/autoload.php';
    
    use KiteConnect\KiteConnect;

    // Initialise.
    $kite = new KiteConnect("bxgemb4liqbi58ki");

    // Assuming you have obtained the `request_token`
    // after the auth flow redirect by redirecting the
    // user to $kite->login_url()
    try {
        $user = $kite->generateSession("request_token_obtained", "aq6upbr6r1xvyj7ubjfbtqcj5n1n625l");
        echo "Authentication successful. \n";
        print_r($user);
    } catch(Exception $e) {
        echo "Authentication failed: ".$e->getMessage();
        throw $e;
    }

   
?>