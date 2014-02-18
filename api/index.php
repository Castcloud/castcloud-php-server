<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->group('/account', function() use($app) {
	$app->response->header('Content-Type', 'application/json');

	$app->post('/login', function() use($app) {
		$username = $app->request->params('username');
		$password = $app->request->params('password');

		$json = array("token" => "sykebil");
		echo json_encode($json);
	});

	$app->get('/ping', function() use($app) {
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
		$json = array("key" => "value", "key2" => "value");
		echo json_encode($json);
	});

	$app->post('/settings', function() use($app) {
		$json = array("status" => "success");
		echo json_encode($json);
	});

	$app->get('/takeout', function() use($app) {

	});

	$app->get('/takeout/opml', function() use($app) {

	});

	$app->post('/takeout/opml', function() use($app) {

	});

});

$app->group('/library', function() use($app) {
	$app->response->header('Content-Type', 'application/json');

	$app->get('/newepisodes', function() use($app) {

	});

	$app->get('/episodes/:castid', function($castid) use ($app) {

	});

	$app->get('/casts', function() use ($app) {

	});

	$app->post('/casts', function() use ($app) {
		$feedurl = $app->request->params('feedurl');

		$json = array("status" => "success");
		echo json_encode($json);
	});

	$app->get('/casts/:tag', function($tag) use ($app) {

	});

	$app->get('/events', function() use ($app) {

	});

	$app->post('/events', function() use ($app) {
		
	});

	$app->get('tags', function() use ($app) {
		
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