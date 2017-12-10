<?php

require_once 'includes/auth.php';
require_once 'includes/cms.php';

if (!phpCAS::isAuthenticated()) {
    phpCAS::forceAuthentication();
} else {
    header('location: ./person.php?rcsId=' . strtolower(phpCAS::getUser()));
}

?>