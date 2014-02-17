<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/login', function() use($app) {
	$app->response->header('Content-Type', 'application/json');

	$json = array("token" => "sykebil");
    echo json_encode($json);
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