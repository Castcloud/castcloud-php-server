<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'password';
$db_name = 'castcloud';
$db_prefix = 'cc_';
$dbh = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
?>
