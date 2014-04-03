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
		json(new newepisodesresult($app->db->get_episodes(null, null, "70", $app->request->params('since'))));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/episodes/{castid}",
	 * 	description="Get all episodes of a cast",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get all episodes of a cast",
	 * 		summary="Get all episodes of a cast",
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
	 * 			description="The casts id",
	 * 			paramType="path",
	 * 			required=true,
	 * 			type="integer"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="exclude",
	 * 			description="Comma separated event ids to exclude. Default: 70",
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
	$app -> get('/episodes/:castid', function($castid) use ($app) {
		$exclude = $app -> request -> params('exclude');
		if ($exclude != null){
			json($app->db->get_episodes($castid, null, $exclude));
		} else {
			json($app->db->get_episodes($castid, null));
		}
	});
	
	/**
	 * @SWG\Api(
	 * 	path="/library/episodes/tag/{tag}",
	 * 	description="Get all episodes of a tag",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get episodes for tag",
	 * 		summary="Get episodes for tag",
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
	 * 			name="tag",
	 * 			description="The tag",
	 * 			paramType="path",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="exclude",
	 * 			description="Comma separated event ids to exclude. Default: 70",
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
	$app -> get('/episodes/tag/:tag', function($tag) use ($app) {
		$exclude = $app -> request -> params('exclude');
		if ($exclude != null){
			json($app->db->get_episodes(null, $tag, $exclude));
		} else {
			json($app->db->get_episodes(null, $tag));
		}
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
	
	/**
	 * @SWG\Api(
	 * 	path="/library/casts.opml",
	 * 	description="Get users subcriptions",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users subcriptions",
	 * 		summary="Get users subcriptions",
	 * 		type="$ref:opml",
	 * 		produces="['text/x-opml']",
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
	$app->get('/casts.opml', function() use($app) {
		opml($app->db->get_opml());
	});
	
	$app -> post('/casts.opml', function() use ($app) {
		json(array("Not" => "Implemented"));
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/casts",
	 * 	description="Subcribe to a cast",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Subcribe to a cast",
	 * 		summary="Subcribe to a cast",
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
		$userid = $app -> userid;
		
		$castid = crawl($feedurl);
		
		$app->db->add_to_label_root("cast/" . $castid);
		
		if ($name == null){
			$castinfo = $app->db->get_cast($castid);
			if (array_key_exists("title",$castinfo)){
				$name = $castinfo["title"];
			} else {
				$name = $feedurl;
			}
		}
		
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$sth = $dbh -> query("SELECT * FROM {$db_prefix}subscription WHERE castid=$castid AND userid=$userid");
		if ($sth && $sth -> rowCount() < 1) {
			$sth = $dbh -> prepare("INSERT INTO {$db_prefix}subscription (castid, name, userid) 
			VALUES($castid, :name, $userid)");
			$sth -> bindParam(":name",$name);
			$sth -> execute();
		}
	});
	
	/**
	 * @SWG\Api(
	 * 	path="/library/casts/{id}",
	 * 	description="Edit a subcription",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Edit a subcription",
	 * 		summary="Edit a subcription",
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
	 * 		@SWG\Parameter(
	 * 			name="name",
	 * 			description="The feeds display name",
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
		$name = $app -> request -> params('name');
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$sth = $dbh -> prepare("UPDATE {$db_prefix}subscription
			SET name=:name
			WHERE CastID=:id");
		$sth -> bindParam(":name",$name);
		$sth -> bindParam(":id",$id);
		$sth -> execute();
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
		$GLOBALS['dbh']->exec("DELETE FROM {$db_prefix}subscription WHERE castid=$id AND userid=$userid");
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
		
		$itemid = $app->request->params('itemid');
		$since = $app->request->params('since');
		$limit = $app->request->params('limit');

		json(new eventsresult($app->db->get_events($itemid, $since, $limit)));
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
	});

	/**
	 * @SWG\Api(
	 * 	path="/library/label",
	 * 	description="Get users labels",
	 * 	@SWG\Operation(
	 * 		method="GET",
	 * 		nickname="Get users labels",
	 * 		summary="Get users labels",
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
	$app -> get('/label', function() use ($app) {	
		json($app->db->get_label());
	});
	
	/**
	 * @SWG\Api(
	 * 	path="/library/label",
	 * 	description="Create a new label",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Create a new label",
	 * 		summary="Create a new label",
	 * 		type="void",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="name",
	 * 			description="The name of the new label",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="content",
	 * 			description="The content of the label. See GET label for formatting",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="expanded",
	 * 			description="Wether or not the label is expanded in the client UI. Default false. root is always true.",
	 * 			paramType="form",
	 * 			required=false,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/label', function() use ($app) {
		$name = $app -> request -> params('name');
		$content = $app -> request -> params('content');
		$expanded = $app -> request -> params('expanded');
		
		if (!(strpos($name,"label/") === 0)){
			$name = "label/" . $name; 
		}
				
		if($expanded != null){
			$expanded = ($expanded == "true");
		}
		
		if (($expanded == null) || ($name == "root")){
			$expanded = ($name == "root");
		}
		
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$userid = $app -> userid;
		
		$sth = $dbh -> prepare("SELECT count(*)
			FROM {$db_prefix}label AS label
			WHERE
			label.name = :name
			AND label.userid=$userid");
		$sth -> bindParam(":name",$name);
		$sth -> execute();
		
		$result = $sth -> fetchAll();
		
		if ($result["0"]["0"] < 1){
			$sth = $dbh -> prepare("INSERT INTO {$db_prefix}label
				(userid, name, content, expanded) 
				VALUES($userid, :name, :content, :expanded)");
			$sth -> bindParam(":name",$name);
			$sth -> bindParam(":content",$content);
			$sth -> bindParam(":expanded",$expanded);
			$sth -> execute();
		} else {
			$app->halt(400, "Exsisting label");
		}
	});
	
		/**
	 * @SWG\Api(
	 * 	path="/library/label/{id}",
	 * 	description="Edit a label",
	 * 	@SWG\Operation(
	 * 		method="POST",
	 * 		nickname="Edit a label",
	 * 		summary="Edit a label",
	 * 		type="void",
	 * 		@SWG\Parameter(
	 * 			name="Authorization",
	 * 			description="clients login token",
	 * 			paramType="header",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="name",
	 * 			description="The name of the new label",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="content",
	 * 			description="The content of the label. See GET label for formatting",
	 * 			paramType="form",
	 * 			required=true,
	 * 			type="string"
	 * 		),
	 * 		@SWG\Parameter(
	 * 			name="expanded",
	 * 			description="Wether or not the label is expanded in the client UI. Default false. root is always true.",
	 * 			paramType="form",
	 * 			required=false,
	 * 			type="string"
	 * 		),
	 * 		@SWG\ResponseMessage(
	 * 			code=400,
	 * 			message="Bad token"
	 * 		)
	 * 	)
	 * )
	 */
	$app -> post('/label/:id', function($id) use ($app) {
		$name = $app -> request -> params('name');
		$content = $app -> request -> params('content');
		$expanded = $app -> request -> params('expanded');
		
		if (!(strpos($name,"label/") === 0) && !($name == "root")){
			$name = "label/" . $name; 
		}
				
		if($expanded != null){
			$expanded = ($expanded == "true");
		}
		
		if (($expanded == null) || ($name == "root")){
			$expanded = ($name == "root");
		}
		
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$userid = $app -> userid;
		
		$sth = $dbh -> prepare("SELECT *
			FROM {$db_prefix}label AS label
			WHERE
			label.LabelID = :id
			AND label.userid=$userid");
		$sth -> bindParam(":id",$id);
		$sth -> execute();
		
		$result = $sth -> fetchAll();
		
		if ($result["0"]["0"] < 1){
			$sth = $dbh -> prepare("INSERT INTO {$db_prefix}label
				(userid, name, content, expanded) 
				VALUES($userid, :name, :content, :expanded)");
			$sth -> bindParam(":name",$name);
			$sth -> bindParam(":content",$content);
			$sth -> bindParam(":expanded",$expanded);
			$sth -> execute();
		} else {
			$app->halt(400, "Exsisting label");
		}
	});

});

$app -> run();
?>
