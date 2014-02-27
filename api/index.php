<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
GLOBAL $app;

include 'cc-settings.php';
GLOBAL $dbh;

include 'authmiddleware.php';
include 'crawler.php';
include 'util.php';

$app -> add(new AuthMiddleware());

/**
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.2",
 *   basePath="http://api.castcloud.org/api",
 *   resourcePath="/account",
 *   description="Account related operations",
 *   produces="['application/json','application/xml']"
 * )
 */

$app -> group('/account', function() use ($app) {
	/**
	 * @SWG\Api(
	 * 	path="/account/login",
	 * 	description="User login.",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Login",
	 * 		summary="Get access tolken",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="username",
	 * 			description="Users username",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="password",
	 * 			description="Users password",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="clientname",
	 * 			description="Client Name",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="clientdescription",
	 * 			description="Client Description. e.g. Sallys iPad",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="clientversion",
	 * 			description="Client Version",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="uuid",
	 * 			description="Universally unique identifier. String used for uniqely identifying an instanse of an application.",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="apikey",
	 * 			description="Clients official apikey. Not yet implemented",
	 * 			paramType="form",
	 * 			required=false,
	 * 			type="string"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/login', function() use ($app) {
		$username = $app -> request -> params('username');
		$password = $app -> request -> params('password');
		$clientname = $app -> request -> params('clientname');
		$clientdescription = $app -> request -> params('clientdescription');
		$clientversion = $app -> request -> params('clientversion');
		$uuid = $app -> request -> params('uuid');
		$apikey = $app -> request -> params('apikey');

		$required = array("username", "password", "clientname", "clientdescription", "clientversion", "uuid");
		$status = "The following parameters are missing: ";
		$missing = 0;
		foreach ($required as $key) {
			if (!array_key_exists($key, $app -> request -> params())) {
				$status .= $key . ", ";
				$missing++;
			}
		}
		$status = substr($status, 0, strlen($status) - 2);
		if ($missing > 0) {
			json(array("status" => $status));
			$app -> stop();
		}

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> query("SELECT * FROM users WHERE username='$username'");
		if ($sth) {
			if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
				$userid = $result['UserID'];
				$salt = $result['Salt'];
				if ($result['Password'] == md5($password . $salt)) {
					$sth = $dbh -> query("SELECT * FROM client WHERE Name='$clientname'");
					if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
						$sth = $dbh -> query("SELECT * FROM clientauthorization WHERE userid='$userid'");
						if ($result = $sth -> fetch(PDO::FETCH_ASSOC)) {
							$token = $result['Tolken'];
						}
					} else {
						$token = base64_encode(random_bytes(32));
						$sth = $dbh -> prepare("INSERT INTO client (name) VALUES(:clientname)");
						$sth -> bindParam(':clientname', $clientname, PDO::PARAM_STR);
						$sth -> execute();
						$sth = $dbh -> prepare("INSERT INTO clientauthorization (userid, clientid, tolken, clientdescription, clientversion, uuid, seents) "
						 . "VALUES($userid, " . $dbh -> lastInsertId() . ", '$token', :clientdescription, :clientversion, :uuid, " . time() . ")");
						$sth -> bindParam(':clientdescription', $clientdescription, PDO::PARAM_STR);
						$sth -> bindParam(':clientversion', $clientversion, PDO::PARAM_STR);
						$sth -> bindParam(':uuid', $uuid, PDO::PARAM_STR);
						$sth -> execute();
					}

					json(array("token" => $token));
				} else {
					json(array("status" => "Login failed"));
				}
			} else {
				json(array("status" => "Login failed"));
			}
		} else {
			$error = $dbh -> errorInfo();
			error_log("Castcloud database error: " . $error[2], 0);
			json(array("status" => "Database fail"));
		}
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/ping",
	 * 	description="Tests if tolken works",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Ping",
	 * 		summary="Test tolken",
	 * 		type="Herp",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login tolken",
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
	 * 			description="clients login tolken",
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
	 * 			description="clients login tolken",
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

$app -> group('/library', function() use ($app) {

	$app -> get('/newepisodes', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app -> get('/episodes/:castid', function($castid) use ($app) {
		$episodes = array();

		$titles = crawler_get_all($castid, "channel/item/title");
		$descriptions = crawler_get_all($castid, "channel/item/description");

		for ($i = 0; $i < sizeof($titles); $i++) {
			array_push($episodes, array("title" => $titles[$i], "description" => $descriptions[$i]));
		}

		json($episodes);
	});

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
