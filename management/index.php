<?php
require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

include 'installmiddleware.php';
include '../api/util.php';

session_cache_limiter(false);
session_start();

$app = new \Slim\Slim();
GLOBAL $app;

if (file_exists('../api/cc-settings.php')) {
	include '../api/cc-settings.php';
	GLOBAL $dbh;
}

$app->add(new InstallMiddleware());

$app->get('/', function() {
	if (isset($_SESSION['login'])) {
		$status = "Hai ".$_SESSION['username'];
	}
	include 'templates/login.phtml';
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
			echo $salt."\n";
			echo md5($password.$salt)."\n";
			echo $result['Password']."\n";
			if ($result['Password'] == md5($password.$salt)) {
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

$app->post('/install', function() use($app) {
	$file = fopen('../api/cc-settings.php', 'w');
	fputs($file, "<?php\n\$db_user = '".$app->request->params("db_username")."';\n");
	fputs($file, "\$db_pass = '".$app->request->params("db_password")."';\n");
	fputs($file, "\$db_host = '".$app->request->params("db_hostname")."';\n");
	fputs($file, "\$db_port = '".$app->request->params("db_port")."';\n");
	fputs($file, "\$db_name = '".$app->request->params("db_name")."';\n");
	fputs($file, "\$db_prefix = '".$app->request->params("db_prefix")."';\n");
	fputs($file, '$dbh = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);'."\n?>");
	fclose($file);

	include '../api/cc-settings.php';

	$username = $app->request->params("cc_username");
	$password = $app->request->params("cc_password");
	$salt = base64_encode(random_bytes(16));

	$dbh->exec("INSERT INTO users (username, name, mail, password, salt) VALUES('$username', '', '', md5('$password$salt'), '$salt')");

	$app->response->redirect($_SERVER['HTTP_REFERER']);
});

$app->run();
?>