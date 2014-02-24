<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
GLOBAL $app;

include 'cc-settings.php';
GLOBAL $dbh;

include 'authmiddleware.php';
include 'util.php';

$app->add(new AuthMiddleware());

$app->group('/account', function() use($app) {

	$app->post('/login', function() use($app) {
		$username = $app->request->params('username');
		$password = $app->request->params('password');

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh->query("SELECT * FROM users WHERE username='$username'");
		if ($sth) {
			if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
				$userid = $result['UserID'];
				$salt = $result['Salt'];
				if ($result['Password'] == md5($password.$salt)) {
					$sth = $dbh->query("SELECT * FROM clientauthorization WHERE userid='$userid'");
					if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
						$token = $result['Tolken'];
					}
					else {
						$token = base64_encode(random_bytes(32));

						$dbh->exec("INSERT INTO clientauthorization (userid, clientid, tolken, clientdescription, clientversion, uuid, seents) VALUES($userid, 1, '$token', 'Castcloud', '1.0', '', 1881)");
					}

					json(array("token" => $token));
				}
				else {
					json(array("status" => "Login failed"));
				}
			}
			else {
				json(array("status" => "Login failed"));
			}
		}
	});

	$app->get('/ping', function() use($app) {
		json(array("status" => "Logged in"));
	});

	$app->get('/settings', function() use($app) {
		$app->response->header('Content-Type', 'application/json');

		$json = array("key" => "value", "key2" => "value");
		echo json_encode($json);
	});

	$app->post('/settings', function() use($app) {
		$app->response->header('Content-Type', 'application/json');

		$json = array("status" => "success");
		echo json_encode($json);
	});

	$app->get('/takeout', function() use($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->get('/takeout/opml', function() use($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->post('/takeout/opml', function() use($app) {
		$app->response->header('Content-Type', 'application/json');
	});

});

$app->group('/library', function() use($app) {

	$app->get('/newepisodes', function() use($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->get('/episodes/:castid', function($castid) use ($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->get('/casts', function() use ($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->post('/casts', function() use ($app) {
		$app->response->header('Content-Type', 'application/json');

		$feedurl = $app->request->params('feedurl');

		$json = array("status" => "success");
		echo json_encode($json);
	});

	$app->get('/casts/:tag', function($tag) use ($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->get('/events', function() use ($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->post('/events', function() use ($app) {
		$app->response->header('Content-Type', 'application/json');
	});

	$app->get('tags', function() use ($app) {
		$app->response->header('Content-Type', 'application/json');
	});

});

$app->run();

/**
 * Eksempel som kompilerer:
 *
 * @SWG\Resource(
 *		basePath="http://localhost/api",
 *      resourcePath="/login",
 *      @SWG\Api(
 *          path="/login",
 *          @SWG\Operation(
 *              nickname="test",
 *              method="GET",
 *              summary="This is a test",
 *				type="Herp"
 *          )
 *      )
 * )
 */
?>