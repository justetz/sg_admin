<?php

require_once 'includes/auth.php';

if (phpCAS::isAuthenticated()) {
    phpCAS::logout();
} else {
    header('location: ./index.php');
}

?>