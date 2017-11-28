<?php

require_once realpath($_SERVER["DOCUMENT_ROOT"]) . "/CAS-1.3.5/CAS.php";
require_once 'cms.php';

phpCAS::client(CAS_VERSION_2_0, 'cas-auth.rpi.edu', 443, '/cas/');
phpCAS::setCasServerCACert("./cacert.pem");

$authorized = false;
if(phpCAS::isAuthenticated()) {
    $rcsId = strtolower(phpCAS::getUser());
    $authorizedUsers = CmsApi::getAllUsers(32171);
    foreach ($authorizedUsers as $au) {
        if ($au['username'] == $rcsId) {
            $authorized = true;
        }
    }
}
define('IS_AUTHORIZED', $authorized);

function blockUnauthorized() {
    if(!IS_AUTHORIZED) {
        header('location: ./logout.php');
        exit;
    }
}