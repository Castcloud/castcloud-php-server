<?php
//default host?
//base path?

$db-host = 'localhost';
$db-user = 'username';
$db-pass = 'password';
$db-name = 'castcloud';
$db-prefix = 'cc_';
$dbh = new PDO("mysql:host=$db-host;dbname=mysql", $db-user, $db-pass);
?>
