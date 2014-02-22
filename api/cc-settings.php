<?php
//default host?
//base path?

$db_host = 'localhost';
$db_user = 'username';
$db_pass = 'password';
$db_name = 'castcloud';
$db_prefix = 'cc_';
$dbh = new PDO("mysql:host=$db_host;dbname=mysql", $db_user, $db_pass);
?>
