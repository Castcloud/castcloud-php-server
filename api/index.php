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
		$settings = array();

		$sth = $GLOBALS['dbh']->query("SELECT * FROM setting WHERE userid=$app->userid");
		if ($sth) {
			foreach ($sth as $row) {
				$settings[$row['Setting']] = $row['Value'];
			}
		}

		json($settings);
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
		if ($app->request->params('json') == null) {
			$settings = $app->request->params();
		}
		else {			
			$settings = json_decode($app->request->params('json'));
		}

		$dbh = $GLOBALS['dbh'];
		foreach($settings as $key => $value) {
			$sth = $dbh->query("SELECT * FROM setting WHERE userid=$app->userid AND setting='$key'");
			if ($sth && $sth->rowCount() > 0) {
				$dbh->exec("UPDATE setting SET value='$value' WHERE userid=$app->userid AND setting='$key'");				
			}
			else {
				$dbh->exec("INSERT INTO setting (userid, setting, value) VALUES($app->userid, '$key', '$value')");
			}
		}

		json(array("status" => "success"));
	});

	$app -> get('/takeout', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> get('/takeout.opml', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> post('/takeout.opml', function() use ($app) {
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
	 * 	path="/library/newepisodes",
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
		json(crawler_get_new_episodes($app->request->params('since')));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/episodes/{castid}",
	 * 	description="Get all episodes of a cast",
	 * 	@SWG\Operation(
	 * 		method="GET",
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
		json(crawler_get_episodes($castid));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts",
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
	$app -> get('/casts', function() use ($app) {
		json(crawler_get_casts());
	});

	$app->get('/casts.opml', function() use($app) {
		json(array("Not" => "Implemented"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts",
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
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="feedurl",
	 * 			description="URL of podcast feed",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/casts', function() use ($app) {
		$feedurl = $app -> request -> params('feedurl');
		$feedid = crawl($feedurl);
		$userid = $app -> userid;

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query("SELECT * FROM subscription WHERE feedid=$feedid AND userid=$userid");
		if ($sth && $sth -> rowCount() < 1) {
			$dbh -> exec("INSERT INTO subscription (feedid, tags, userid) VALUES($feedid, 'bjarne,nils', $userid)");
		}

		json(array("status" => "success"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts/{tag}",
	 * 	description="Get users subcriptions for spesific tag",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users subcriptions for spesific tag",
	 * 		summary="Get users subcriptions for spesific tag",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="tag",
	 * 			description="filter by tag",
	 * 			paramType="path",
	 * 			required=false,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/casts/:tag', function($tag) use ($app) {
		json(crawler_get_casts($tag));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/events",
	 * 	description="Get events",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users tags",
	 * 		summary="Get users tags",
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
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="CastID",
	 * 			description="filter by CastID",
	 * 			paramType="query",
	 * 			required=false,
	 * 			type="integer"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="ItemID",
	 * 			description="filter by ItemID",
	 * 			paramType="query",
	 * 			required=false,
	 * 			type="integer"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/events', function() use ($app) {
		$events = array("timestamp" => time(), "events" => array());
		$query = "SELECT * FROM event WHERE userid=$app->userid";
		$itemid = $app->request->params('itemid');
		$since = $app->request->params('since');

		if ($itemid != null) {
			$query.=" AND itemid=$itemid";
		}
		if ($since != null) {
			$query.=" AND clientts > $since";
		}

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query($query);
		if ($sth) {
			foreach ($sth as $row) {
				array_push($events["events"], array(
					"type" => $row['Type'],
					"itemid" => $row['ItemID'],
					"event" => $row['Event'],
					"clientts" => $row['ClientTS']));
			}
		}

		json($events);
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/events",
	 * 	description="Add events",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Add events",
	 * 		summary="Add events",
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
	 * 			description="New events (TBD)",
	 * 			paramType="body",
	 * 			required=true,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/events', function() use ($app) {
		$receivedts = time();
		if ($app->request->params('json') == null) {
			$type = $app->request->params('type');
			$itemid = $app->request->params('itemid');
			$event = $app->request->params('event');
			$clientts = $app->request->params('clientts');

			$GLOBALS['dbh']->exec("INSERT INTO event (userid, type, itemid, event, clientts, receivedts, uniqueclientid) VALUES($app->userid, $type, $itemid, '$event', $clientts, $receivedts, $app->uniqueclientid)");
		}
		else {			
			$json = json_decode($app->request->params('json'));
			foreach ($json as $event) {
				$GLOBALS['dbh']->exec("INSERT INTO event (userid, type, itemid, event, clientts, receivedts, uniqueclientid) VALUES($app->userid, $event->type, $event->itemid, '$event->event', $event->clientts, $receivedts, $app->uniqueclientid)");
			}
		}
		
		json(array("Status" => "Success"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/tags",
	 * 	description="Get users tags",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users tags",
	 * 		summary="Get users tags",
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
	$app -> get('/tags', function() use ($app) {
		$dbh = $GLOBALS['dbh'];
		$userid = $app -> userid;
		$tags = array();
		$sth = $dbh -> query("SELECT Tags FROM subscription WHERE UserID=$userid");
		if ($sth){
			foreach ($sth as $row){
				$tag = $row['Tags'];
				if ($tag != '' && !strpos($tag,',')){
					array_push($tags, $tag);
				}
				if (strpos($tag,',')){
					$tags = array_merge($tags, explode( ',', str_replace(' ','',$tag)));
				}
			}
		}
		asort($tags);	
		json(array_values(array_unique($tags)));
	});

});

$app -> run();
?>
