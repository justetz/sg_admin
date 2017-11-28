<?php
require_once 'includes/auth.php';

if (phpCAS::isAuthenticated()) {
    header('location: ./dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<link rel="icon" type="image/png" href="assets/img/favicon.ico">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<title>Welcome | SG Management Tool</title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
    <meta name="viewport" content="width=device-width" />


    <!-- Bootstrap core CSS     -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Animation library for notifications   -->
    <link href="assets/css/animate.min.css" rel="stylesheet"/>

    <!--  Light Bootstrap Table core CSS    -->
    <link href="assets/css/light-bootstrap-dashboard.css" rel="stylesheet"/>

    <link href="styles/main.css" rel="stylesheet"/>

    <!--     Fonts and icons     -->
    <link href="http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
    <link href='http://fonts.googleapis.com/css?family=Roboto:400,700,300' rel='stylesheet' type='text/css'>
    <link href="assets/css/pe-icon-7-stroke.css" rel="stylesheet" />
</head>
<body class="home">
    <div class="container-fluid">
        <div class="panel panel-default login-panel">
            <div class="panel-heading">
                <h4>RPI Student Government Management System</h4>
            </div>
            <div class="panel-body">
                <a href="/login.php" class="btn btn-danger btn-lg btn-block center-block btn-login">RCS Login</a>
                <a href="https://sg.rpi.edu" class="btn btn-default">Return to Homepage</a>
            </div>
            <div class="panel-footer text-muted small">
                This application is intended exclusively for RPI Student Government use. Unauthorized access is prohibited and subject to RPI's <a href="http://policy.rpi.edu/policy/Cyber_Citizenship_Policy">Cyber-Citizenship Policy</a>.
            </div>
        </div>
    </div>
</body>
</html>