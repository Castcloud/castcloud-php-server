<?php
require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

include 'authmiddleware.php';
include 'installmiddleware.php';
include '../api/util.php';

session_cache_limiter(false);
session_start();

$app = new \Slim\Slim();
GLOBAL $app;

if (file_exists('../api/cc-settings.php')) {
	include '../api/cc-settings.php';
	GLOBAL $db_prefix,$dbh;
}

$app->add(new InstallMiddleware());
$app->add(new AuthMiddleware());

$app->get('/', function() {
	if (isset($_SESSION['login'])) {
		$username = $_SESSION['username'];

		$db_prefix = $GLOBALS['db_prefix'];
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query("SELECT * FROM {$db_prefix}users WHERE username='$username'");

		if ($sth) {
			if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
				$usernames = array(array("username" => $username));
				$userlevel = $result['UserLevel'];
				if ($userlevel >= 100) {
					$sth = $dbh -> query("SELECT username, userlevel FROM {$db_prefix}users");
					$usernames = $sth -> fetchAll();
				}
			}
		}
		include 'templates/userlist.phtml';
	}
	else {
		include 'templates/login.phtml';
	}
});

$app->post('/login', function() use($app) {
	$username = $app->request->params('username');
	$password = $app->request->params('password');

	$dbh = $GLOBALS['dbh'];
	$db_prefix = $GLOBALS['db_prefix'];
	$sth = $dbh -> query("SELECT * FROM {$db_prefix}users WHERE username='$username'");
	if ($sth) {
		if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
			$userid = $result['UserID'];
			$salt = $result['Salt'];
			if ($result['Password'] == md5($password.$salt)) {
				$_SESSION['login'] = true;
				$_SESSION['username'] = $username;
			}
		}
	}

	$app->response->redirect($_SERVER['HTTP_REFERER']);
});

$app->get('/edit/:username', function($username) use($app) {
	$db_prefix = $GLOBALS['db_prefix'];
	$dbh = $GLOBALS['dbh'];
	$sth = $dbh -> query("SELECT * FROM {$db_prefix}users WHERE username='$username'");

	if ($sth) {
		if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
			$name = $result['Name'];
			$mail = $result['Mail'];
		}
	}
	include 'templates/useredit.phtml';


});

$app->get('/adduser', function() use($app) {
	include 'templates/adduser.phtml';
});

$app->post('/adduser', function() use($app) {
	if(($username = $app->request->params("username")) && 
		($name = $app->request->params("name")) && 
		($mail = $app->request->params("mail")) && 
		($password = $app->request->params("password")) && 
		($salt = base64_encode(random_bytes(16))) ){
			$dbh = $GLOBALS['dbh'];
			$userlevel = 0;
			$db_prefix = $GLOBALS['db_prefix'];
			$stmt = $dbh->prepare("INSERT INTO {$db_prefix}users (userlevel, username, name, mail, password, salt) VALUES (:userlevel, :username, :name, :mail, :password, :salt)");
			$values = array(':userlevel'=>$userlevel, ':username'=>$username, ':name'=>$name,':mail'=>$mail,':password'=>md5($password.$salt), ':salt'=>$salt);
			if ($stmt->execute($values)){
				$app->flash('adduser', "Added User");
				$app->response->redirect($_SERVER['HTTP_REFERER']);
			} else {
				$app->flash('error', "Username taken");
				$app->response->redirect($_SERVER['HTTP_REFERER']);
			}
		} else {
		$app->flash('error', "Please insert into all fields");
		$app->response->redirect($_SERVER['HTTP_REFERER']);
	}

});

$app->post('/edit/:username', function($username) use($app) {
	
	if(($userlevel = $app->request->params("userlevel")) != null) {
		$db_prefix = $GLOBALS['db_prefix'];
		$GLOBALS['dbh']->exec("UPDATE {$db_prefix}users SET UserLevel='$userlevel' WHERE Username='$username'");
	}	
	if($name = $app->request->params("name")){
		$db_prefix = $GLOBALS['db_prefix'];
		$GLOBALS['dbh']->exec("UPDATE {$db_prefix}users SET Name='$name' WHERE username='$username'");
	}
	if($mail = $app->request->params("mail")){
		$db_prefix = $GLOBALS['db_prefix'];
		$GLOBALS['dbh']->exec("UPDATE {$db_prefix}users SET Mail='$mail' WHERE username='$username'");
	}
	if($password = $app->request->params("password")){
		$db_prefix = $GLOBALS['db_prefix'];
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query("SELECT salt FROM {$db_prefix}users WHERE username='$username'");
		if ($sth) {
			if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
				$salt = $result['salt'];
			}
		}
		$GLOBALS['dbh']->exec("UPDATE {$db_prefix}users SET Password=md5('$password$salt') WHERE username='$username'");


	}
	$app->response->redirect($_SERVER['HTTP_REFERER']);

});

$app->post('/logout', function() use($app) {
	session_destroy();
	$app->response->redirect($_SERVER['HTTP_REFERER']);
});

$app->post('/install', function() use($app) {
	$db_user = $app->request->params("db_username");
	$db_pass = $app->request->params("db_password");
	$db_host = $app->request->params("db_hostname");
	$db_port = $app->request->params("db_port");
	$db_name = $app->request->params("db_name");
	$db_prefix = $app->request->params("db_prefix");

	$file = fopen('../api/cc-settings.php', 'w');
	fputs($file, "<?php\n\$db_user = '$db_user';\n");
	fputs($file, "\$db_pass = '$db_pass';\n");
	fputs($file, "\$db_host = '$db_host';\n");
	fputs($file, "\$db_port = '$db_port';\n");
	fputs($file, "\$db_name = '$db_name';\n");
	fputs($file, "\$db_prefix = '$db_prefix';\n");
	fputs($file, "\$dbh = new PDO(\"mysql:host=\$db_host;port=\$db_port;dbname=\$db_name\", \$db_user, \$db_pass);\n?>");
	fclose($file);

	$dbh = new PDO("mysql:host=$db_host;port=$db_port", $db_user, $db_pass);
	$dbh->exec("CREATE DATABASE IF NOT EXISTS $db_name");
	$dbh->exec("USE $db_name");

	$sql = file_get_contents('../api/db.sql');
	$sql = str_replace("prefix_", $db_prefix, $sql);
	$dbh->exec($sql);

	$username = $app->request->params("cc_username");
	$password = $app->request->params("cc_password");
	$salt = base64_encode(random_bytes(16));

	$dbh->exec("INSERT INTO {$db_prefix}users (userlevel, username, name, mail, password, salt) VALUES(100, '$username', '', '', md5('$password$salt'), '$salt')");

	$app->response->redirect($_SERVER['HTTP_REFERER']);
});

$app->run();
?>
