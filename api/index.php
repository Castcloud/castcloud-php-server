<?php
require '../lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
GLOBAL $app;

include 'cc-settings.php';
GLOBAL $db_prefix,$dbh;

include 'authmiddleware.php';
include 'util.php';
include 'crawler.php';
include 'login.php';
include 'db.php';

$app->db = new DB($dbh);

$app -> add(new AuthMiddleware());

/**
 * @SWG\Resource(
 * 	apiVersion="1.0.0",
 * 	swaggerVersion="1.2",
 * 	basePath="http://api.castcloud.org/api",
 * 	resourcePath="/account",
 * 	description="Account related operations",
 * 	produces="['application/json']"
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
	 * 		type="void",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=200,
	 * 			message="All ok"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/ping', function() {});

	/**
	 * @SWG\Api(
	 * 	path="/account/settings",
	 * 	description="Settings",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get Settings",
	 * 		summary="Get Settings",
	 * 		type="array",
	 * 		items="$ref:setting",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/settings', function() use ($app) {
		json($app->db->get_settings());
	});

	/**
	 * @SWG\Api(
	 * 	path="/account/settings",
	 * 	description="Settings",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Set Settings",
	 * 		summary="Set Settings",
	 * 		type="void",
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
	 * 			type="array",
	 * 			items="$ref:setting"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
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
		$db_prefix = $GLOBALS['db_prefix'];
		foreach($settings as $key => $value) {
			$sth = $dbh->query("SELECT * FROM {$db_prefix}setting WHERE userid=$app->userid AND setting='$key'");
			if ($sth && $sth->rowCount() > 0) {
				$dbh->exec("UPDATE {$db_prefix}setting SET value='$value' WHERE userid=$app->userid AND setting='$key'");				
			}
			else {
				$dbh->exec("INSERT INTO {$db_prefix}setting (userid, setting, value) VALUES($app->userid, '$key', '$value')");
			}
		}
	});
	
	/**
	 * @SWG\Api(
	 * 	path="/account/settings/{settingid}",
	 * 	description="Settings",
	 * 	@SWG\Operation(
	 * 		method="DELETE",
	 * 		nickname="Delete Setting",
	 * 		summary="Delete Setting",
	 * 		type="void",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="settingid",
	 * 			description="ID of the setting that is to be removed",
	 * 			paramType="path",
	 * 			required=true,
	 * 			type="integer"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/settings/:settingid', function($settingid) use ($app) {
		$userid = $app->userid;
		$db_prefix = $GLOBALS['db_prefix'];
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare("DELETE FROM {$db_prefix}setting WHERE settingid=:settingid AND userid=:userid");
		$sth->bindParam(":settingid",$settingid);
		$sth->bindParam(":userid",$userid);
		$sth->execute();
	});

	$app -> get('/takeout', function() use ($app) {
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
     * 		type="newepisodesresult",
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
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/newepisodes', function() use ($app) {
		include_once 'models/newepisodesresult.php';
		json(new newepisodesresult($app->db->get_new_episodes($app->request->params('since'))));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/episodes/{castid}",
	 * 	description="Get all episodes of a cast",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get all episodes",
	 * 		summary="Get all episodes",
     * 		type="array",
     * 		items="$ref:episode",
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
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/episodes/:castid', function($castid) use ($app) {
		json($app->db->get_episodes($castid));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts",
	 * 	description="Get users subcriptions",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users subcriptions",
	 * 		summary="Get users subcriptions",
	 * 		type="array",
	 * 		items="$ref:cast",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/casts', function() use ($app) {
		json($app->db->get_casts());
	});

	$app->get('/casts.opml', function() use($app) {
		opml($app->db->get_opml());
	});
	
	$app -> post('/casts.opml', function() use ($app) {
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
	 * 		type="void",
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
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="name",
	 * 			description="The displayname for the cast",
	 * 			paramType="form",
	 * 			required=false,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="tags",
	 * 			description="Comma separated tags",
	 * 			paramType="form",
	 * 			required=false,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="arrangement",
	 * 			description="Integer describing where in the list the cast is to be located",
	 * 			paramType="form",
	 * 			required=false,
	 * 			type="integer"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/casts', function() use ($app) {
		$feedurl = $app -> request -> params('feedurl');
		$name = $app -> request -> params('name');
		$tags = $app -> request -> params('tags');
		$arrangement = $app -> request -> params('arrangement');
		$userid = $app -> userid;
		
		$feedid = crawl($feedurl);
		
		if ($name == null){
			$castinfo = $app->db->get_cast($feedid);
			if (array_key_exists("title",$castinfo)){
				$name = $castinfo["title"];
			} else {
				$name = $feedurl;
			}
		}
		
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$sth = $dbh -> query("SELECT * FROM {$db_prefix}subscription WHERE feedid=$feedid AND userid=$userid");
		if ($sth && $sth -> rowCount() < 1) {
			if($arrangement != null){
				//SET id = 101 WHERE id = 80; SET id = id + 1 WHERE id BETWEEN 20 AND 79; SET id = 20 WHERE id = 101;
				$sth = $dbh -> prepare("UPDATE {$db_prefix}subscription
					SET arrangement = arrangement + 1 
					WHERE arrangement >= :arrangement
					AND userid=:userid
					AND arrangement IS NOT NULL");
				$sth -> bindParam(":arrangement",$arrangement);
				$sth -> bindParam(":userid",$userid);
				$sth -> execute();
			}
			$sth = $dbh -> prepare("INSERT INTO {$db_prefix}subscription (feedid, name, tags, arrangement, userid) 
			VALUES($feedid, :name, :tags, :arrangement, $userid)");
			$sth -> bindParam(":name",$name);
			$sth -> bindParam(":tags",$tags);
			$sth -> bindParam(":arrangement",$arrangement);
			$sth -> execute();
		}
	});
	
	/**
	 * @SWG\Api(
	 * 	path="/library/casts/{id}",
	 * 	description="Get users subcriptions",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Get users subcriptions",
	 * 		summary="Get users subcriptions",
	 * 		type="void",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="tags",
	 * 			description="Comma separated tags",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/casts/:id', function($id) use ($app) {
		$tags = $app -> request -> params('tags');

		$userid = $app -> userid;

		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$sth = $dbh -> query("SELECT * FROM {$db_prefix}subscription WHERE feedid=$feedid AND userid=$userid");
		if ($sth && $sth -> rowCount() < 1) {
			$sth = $dbh -> prepare("UPDATE 
				{$db_prefix}subscription 
				SET tags=:tags 
				WHERE FeedID = :id");
			$sth -> bindParam(":tags",$tags);
			$sth -> bindParam(":id",$id);
			$sth -> execute();
		}
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts/{id}",
	 * 	description="Unsubscribe from a cast",
	 * 	@SWG\Operation(
	 * 		method="DELETE",
	 * 		nickname="Unsubscribe from a cast",
	 * 		summary="Unsubscribe from a cast",
     * 		type="void",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="id",
	 * 			description="The casts id",
	 * 			paramType="path",
	 * 			required=true,
	 * 			type="integer"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app->delete('/casts/:id', function($id) use($app) {
		$userid = $app->userid;
		$db_prefix = $GLOBALS['db_prefix'];
		$GLOBALS['dbh']->exec("DELETE FROM {$db_prefix}subscription WHERE feedid=$id AND userid=$userid");
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts/{tag}",
	 * 	description="Get users subcriptions for spesific tag",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users subcriptions for spesific tag",
	 * 		summary="Get users subcriptions for spesific tag",
	 * 		type="array",
	 * 		items="$ref:cast",
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
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/casts/:tag', function($tag) use ($app) {
		json($app->db->get_casts($tag));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/events",
	 * 	description="Get events",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users tags",
	 * 		summary="Get users tags",
     * 		type="array",
     * 		items="$ref:event",
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
	 * 			name="ItemID",
	 * 			description="filter by ItemID",
	 * 			paramType="query",
	 * 			required=false,
	 * 			type="integer"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/events', function() use ($app) {
		include_once 'models/eventsresult.php';
		
		$itemid = $app->request->params('ItemID');
		$since = $app->request->params('since');

		json(new eventsresult($app->db->get_events($itemid, $since)));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/events",
	 * 	description="Add events",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Add events",
	 * 		summary="Add events",
	 * 		type="void",
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
	 * 			type="array",
	 * 			items="$ref:event"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/events', function() use ($app) {
		$db_prefix = $GLOBALS['db_prefix'];
		$receivedts = time();
				
		$json = json_decode(json_encode($app->request->params('json')));

		foreach ($json as $event) {
			$sth = $GLOBALS['dbh']->prepare("INSERT INTO {$db_prefix}event (userid, type, itemid, positionts, concurrentorder, clientts, receivedts, uniqueclientid) VALUES($app->userid, $event->type, $event->itemid, $event->positionts, :concurrentorder, $event->clientts, $receivedts, $app->uniqueclientid)");
			$sth->bindParam(":concurrentorder", $event->concurrentorder, PDO::PARAM_INT);
			$sth->execute();
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
	 * 		type="array",
	 * 		@SWG\Items("string"),
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> get('/tags', function() use ($app) {
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$userid = $app -> userid;
		$tags = array();
		$sth = $dbh -> query("SELECT Tags FROM {$db_prefix}subscription WHERE UserID=$userid");
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
