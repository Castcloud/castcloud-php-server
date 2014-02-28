<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
GLOBAL $app;

include 'cc-settings.php';
GLOBAL $dbh;

include 'authmiddleware.php';
include 'util.php';
include 'crawler.php';
include 'login.php';

$app -> add(new AuthMiddleware());

/**
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.2",
 *   basePath="http://api.castcloud.org/api",
 *   resourcePath="/account",
 *   description="Account related operations",
 *   produces="['application/json']"
 * )
 */
$app -> group('/account', function() use ($app) {
	
	$app -> post('/login', function() use ($app) {
		post_login($app);
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/ping",
	 * 	description="Tests if token works",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Ping",
	 * 		summary="Test token",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/ping', function() use ($app) {
		json(array("status" => "Logged in"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/settings",
	 * 	description="Settings",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get Settings",
	 * 		summary="Get Settings",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/settings', function() use ($app) {
		json(array("key" => "value", "key2" => "value"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/settings",
	 * 	description="Settings",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Set Settings",
	 * 		summary="Set Settings",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="json",
	 * 			description="New or modified settings (TBD)",
	 * 			paramType="body",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/settings', function() use ($app) {
		json(array("status" => "success"));
	});

	$app -> get('/takeout', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> get('/takeout/opml', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> post('/takeout/opml', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

});

/**
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.2",
 *   basePath="http://api.castcloud.org/api",
 *   resourcePath="/library",
 *   description="Library related operations",
 *   produces="['application/json']"
 * )
 */
$app -> group('/library', function() use ($app) {

	/**
	 * @SWG\Api(
	 * 	path="/account/newepisodes",
	 * 	description="Get new episodes",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get new episodes",
	 * 		summary="Get new episodes",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="since",
	 * 			description="timestamp of last call",
	 * 			paramType="query",
	 * 			required=false,
	 * 			type="integer"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/newepisodes', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/episodes/{castid}",
	 * 	description="Get all episodes of a cast",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Get all episodes",
	 * 		summary="Get all episodes",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="castid",
	 * 			description="The casts castid",
	 * 			paramType="path",
	 * 			required=true,
	 * 			type="integer"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/episodes/:castid', function($castid) use ($app) {
		$episodes = array();

		$titles = crawler_get_all($castid, "channel/item/title");
		$descriptions = crawler_get_all($castid, "channel/item/description");

		for ($i = 0; $i < sizeof($titles); $i++) {
			array_push($episodes, array("title" => $titles[$i], "description" => $descriptions[$i]));
		}

		json($episodes);
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/casts",
	 * 	description="Get users subcriptions",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users subcriptions",
	 * 		summary="Get users subcriptions",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	 //Skal outputte i json og opml
	$app -> get('/casts', function() use ($app) {
		$casts = array();

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query("SELECT * FROM feed");
		if ($sth) {
			foreach ($sth as $row) {
				$feedid = $row['FeedID'];
				array_push($casts,
					array("id" => $feedid,
						"name" => crawler_get($feedid, "channel/title"),
						"description" => crawler_get($feedid, "channel/description"),
						"url" => $row['URL']
					)
				);
			}
		}

		json($casts);
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/casts",
	 * 	description="Get users subcriptions",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Get users subcriptions",
	 * 		summary="Get users subcriptions",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/casts', function() use ($app) {
		$feedurl = $app -> request -> params('feedurl');
		$feedid = crawl($feedurl);
		$userid = $app -> request -> params('userid');

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query("SELECT * FROM subscription WHERE feedid=$feedid AND userid=$userid");
		if ($sth && $sth -> rowCount() < 1) {
			$dbh -> exec("INSERT INTO subscription (feedid, tags, userid) VALUES($feedid, '', $userid)");
		}

		json(array("status" => "success"));
	});

	$app -> get('/casts/:tag', function($tag) use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> get('/events', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> post('/events', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> get('tags', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

});

$app -> run();
?>
