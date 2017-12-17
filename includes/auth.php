<?php

require_once realpath($_SERVER["DOCUMENT_ROOT"]) . '/vendor/autoload.php';
require_once 'cms.php';

phpCAS::client(CAS_VERSION_2_0, 'cas-auth.rpi.edu', 443, '/cas/');
phpCAS::setCasServerCACert("./cacert.pem");

$authorized = false;
if(phpCAS::isAuthenticated()) {
    $rcsId = strtolower(phpCAS::getUser());
    define('RCS_ID', $rcsId);

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
        header("location: ./person.php?rcsId=" . strtolower(phpCAS::getUser()) . "&section=profile");
        exit;
    }
}