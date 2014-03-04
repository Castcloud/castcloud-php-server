<?php
require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

session_cache_limiter(false);
session_start();

$app = new \Slim\Slim();
GLOBAL $app;

include '../api/cc-settings.php';
GLOBAL $dbh;

$app->get('/', function() {
	if (isset($_SESSION['login'])) {
		$status = "Hai ".$_SESSION['username'];
	}
	include('templates/login.phtml');
});

$app->post('/login', function() use($app) {
	$username = $app->request->params('username');
	$password = $app->request->params('password');

	$dbh = $GLOBALS['dbh'];
	$sth = $dbh -> query("SELECT * FROM users WHERE username='$username'");
	if ($sth) {
		if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
			$userid = $result['UserID'];
			$salt = $result['Salt'];
			if ($result['Password'] == md5($password . $salt)) {
				$_SESSION['login'] = true;
				$_SESSION['username'] = $username;
			}
		}
	}

	$app->response->redirect($_SERVER['HTTP_REFERER']);
});

$app->post('/logout', function() use($app) {
	session_destroy();
	$app->response->redirect($_SERVER['HTTP_REFERER']);
});

$app->run();
?>