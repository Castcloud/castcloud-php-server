<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

include 'authmiddleware.php';
include 'util.php';

$app = new \Slim\Slim();

$app->add(new AuthMiddleware());

$app->group('/account', function() use($app) {

	$app->post('/login', function() use($app) {
		$app->response->header('Content-Type', 'application/json');

		$username = $app->request->params('username');
		$password = $app->request->params('password');

		include 'cc-settings.php';

		$sth = $dbh->query("SELECT * FROM users WHERE username='$username'");
		if ($sth) {

		}

		$json = array("token" => base64_encode(random_bytes(32)));
		echo json_encode($json);
	});

	$app->get('/ping', function() use($app) {
		$app->response->header('Content-Type', 'application/json');

		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$status = "sykebil";
		}
		else {
			$status = "no token";
		}
		$userid = $app->request->params('userid');

		$json = array("status" => $status);
		echo json_encode($json);
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