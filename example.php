<?php
require_once 'SameSiteCookieSetter.php';

//set samesite none php cookie
SameSiteCookieSetter::setcookie('samesite_test','testvalue', array('secure' => true, 'samesite' => 'None'));
//SameSiteCookieSetter::setcookie('cookie2','testvalue', array('expires' => time() + 3600, 'httponly' => true, 'secure' => true, 'samesite' => 'None'));

// access cookie value
// echo 'samesite_test:' . $_COOKIE['samesite_test'];
