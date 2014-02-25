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

$app->add(new AuthMiddleware());

$app->group('/account', function() use($app) {

	$app->post('/login', function() use($app) {
		$username = $app->request->params('username');
		$password = $app->request->params('password');
		$clientname = $app->request->params('clientname');
		$clientdescription = $app->request->params('clientdescription');
		$clientversion = $app->request->params('clientversion');
		$uuid = $app->request->params('uuid');
		$apikey = $app->request->params('apikey');

		$required = array("username", "password", "clientname", "clientdescription", "clientversion", "uuid");
		foreach ($required as $key) {
			if (!array_key_exists($key, $app->request->params())) {
				echo "$key is missing :(";
			}
		}

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh->query("SELECT * FROM users WHERE username='$username'");
		if ($sth) {
			if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
				$userid = $result['UserID'];
				$salt = $result['Salt'];
				if ($result['Password'] == md5($password.$salt)) {
					$sth = $dbh->query("SELECT * FROM client WHERE Name='$clientname'");
					if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
						$sth = $dbh->query("SELECT * FROM clientauthorization WHERE userid='$userid'");
              				  	if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
                        				$token = $result['Tolken'];
                				}
                   			}
					else {
						$token = base64_encode(random_bytes(32));
						$sth = $dbh->prepare("INSERT INTO client (name) VALUES(:clientname)");
						$sth->bindParam(':clientname', $clientname, PDO::PARAM_STR);
						$sth->execute();
						$sth = $dbh->prepare("INSERT INTO clientauthorization (userid, clientid, tolken, clientdescription, clientversion, uuid, seents) " .
							"VALUES($userid, " . $dbh->lastInsertId() . ", '$token', :clientdescription, :clientversion, :uuid, " . time() . ")");
						$sth->bindParam(':clientdescription', $clientdescription, PDO::PARAM_STR);
						$sth->bindParam(':clientversion', $clientversion, PDO::PARAM_STR);
						$sth->bindParam(':uuid', $uuid, PDO::PARAM_STR);
                                                $sth->execute();
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
		else {
			error_log("Castcloud database error: " . $dbh->errorInfo()[2], 0);
			json(array("status" => "Database fail"));
		}
	});

	$app->get('/ping', function() use($app) {
		json(array("status" => "Logged in"));
	});

	$app->get('/settings', function() use($app) {
		json(array("key" => "value", "key2" => "value"));
	});

	$app->post('/settings', function() use($app) {
		json(array("status" => "success"));
	});

	$app->get('/takeout', function() use($app) {
		json(array("Not" => "Implemented"));
	});

	$app->get('/takeout/opml', function() use($app) {
		json(array("Not" => "Implemented"));
	});

	$app->post('/takeout/opml', function() use($app) {
		json(array("Not" => "Implemented"));
	});

});

$app->group('/library', function() use($app) {

	$app->get('/newepisodes', function() use($app) {
		json(array("Not" => "Implemented"));
	});

	$app->get('/episodes/:castid', function($castid) use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app->get('/casts', function() use ($app) {
		$casts = array();

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh->query("SELECT * FROM feed");
		if ($sth) {
			foreach ($sth as $row) {
				$feedid = $row['FeedID'];
				array_push($casts, array(
					"name" => crawler_get($feedid, "channel/title"),
					"description" => crawler_get($feedid, "channel/description"), 
					"url" => $row['URL']));
			}
		}

		json($casts);
	});

	$app->post('/casts', function() use ($app) {
		$feedurl = $app->request->params('feedurl');
		$feedid = crawl($feedurl);
		$userid = $app->request->params('userid');

		$dbh = $GLOBALS['dbh'];		
		$sth = $dbh->query("SELECT * FROM subscription WHERE feedid=$feedid AND userid=$userid");
		if ($sth && $sth->rowCount() < 1) {
			$dbh->exec("INSERT INTO subscription (feedid, tags, userid) VALUES($feedid, '', $userid)");
		}

		json(array("status" => "success"));
	});

	$app->get('/casts/:tag', function($tag) use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app->get('/events', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app->post('/events', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	$app->get('tags', function() use ($app) {
		json(array("Not" => "Implemented"));
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
