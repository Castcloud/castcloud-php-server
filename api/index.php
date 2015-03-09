<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header('Access-Control-Allow-Headers: If-None-Match, Authorization');
header('Access-Control-Expose-Headers: Etag');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	exit;
}

require '../lib/Slim/Slim.php';
require '../lib/password_compat/password.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
GLOBAL $app;

include 'cc-settings.php';
GLOBAL $db_prefix, $dbh;

include 'authmiddleware.php';
include 'util.php';
include 'crawler.php';
include 'login.php';
include 'db.php';

$app->db = new DB($dbh, $app);

$app->add(new AuthMiddleware());

$app -> group('/account', function() use ($app) {
	$app -> post('/login', function() use ($app) {
		post_login($app);
	});

	$app -> get('/ping', function() {});

	$app -> get('/settings', function() use ($app) {
		json($app->db->get_settings(), true);
	});

	$app -> post('/settings', function() use ($app) {
		$settings = json_decode($app->request->params("json"));
		$userid = $app->userid;
		
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		foreach($settings as $setting) {
			$ClientID = null;
			
			if($setting->clientspecific == "true"){
				$ClientID = $app->clientid;
			}
			
			$sth = $dbh->prepare ("SELECT * FROM {$db_prefix}setting
				WHERE userid=:userid
				AND setting=:setting
				AND ClientID = :ClientID");
			$sth->bindParam(':userid', $userid);
			$sth->bindParam(':setting', $setting->setting);
			$sth->bindParam(':ClientID', $ClientID);
			$sth->execute();
			
			if ($sth && $sth->rowCount() > 0) {
				$sth = $dbh->prepare ("UPDATE {$db_prefix}setting
					SET value=:value
					WHERE userid=:userid
					AND setting=:setting
					AND ClientID =:ClientID");
				$sth->bindParam(':userid', $userid);
				$sth->bindParam(':setting', $setting->setting);
				$sth->bindParam(':value', $setting->value);
				$sth->bindParam(':ClientID', $ClientID);
				$sth->execute();		
			}
			else {
				$sth = $dbh->prepare ("INSERT INTO {$db_prefix}setting (userid, setting, value, ClientID)
					VALUES(:userid, :setting, :value, :ClientID)");
				$sth->bindParam(':userid', $userid);
				$sth->bindParam(':setting', $setting->setting);
				$sth->bindParam(':value', $setting->value);
				$sth->bindParam(':ClientID', $ClientID);
				$sth->execute();
			}
		}
	});

	$app -> delete('/settings/:settingid', function($settingid) use ($app) {
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

$app -> group('/library', function() use ($app) {

	$app -> get('/newepisodes', function() use ($app) {
		include_once 'models/newepisodesresult.php';
		
		$exclude = $app -> request -> params('exclude');
		$episodes = array();
		
		if ($exclude != null){
			$episodes = $app->db->get_episodes(null, null, null, $app->request->params('since'), $exclude);
		} else {
			$episodes = $app->db->get_episodes(null, null, null, $app->request->params('since'));
		}
		
		json(new newepisodesresult($episodes));
	});

	$app -> get('/episodes/:castid', function($castid) use ($app) {
		$exclude = $app->request->params('exclude');
		json($app->db->get_episodes($castid, null, null, null, $exclude), true);
	});
	
	$app -> get('/episode/:episodeid', function($episodeid) use ($app) {
		$episode = $app->db->get_episodes(null, null, $episodeid, null, "");
		if (!empty($episode)){
			json($episode[0], true);
		}
	});
	
	$app -> get('/episodes/label/:label', function($label) use ($app) {
		$exclude = $app -> request -> params('exclude');
		if ($exclude != null){
			json($app->db->get_episodes(null, $label, null, null, $exclude), true);
		} else {
			json($app->db->get_episodes(null, $label), true);
		}
	});

	$app -> get('/casts', function() use ($app) {
		json($app->db->get_casts(), true);
	});
	
	$app->get('/casts.opml', function() use($app) {
		opml($app->db->get_label(), $app->db->get_casts());
	});
	
	$app -> post('/casts.opml', function() use ($app) {
		set_time_limit(0);
		$opml = $app->request->params('opml');
		$opml = simplexml_load_string($opml);
		$app->db->import_opml($opml->body);
	});

	$app -> post('/casts', function() use ($app) {
		$feedurl = $app -> request -> params('feedurl');
		$name = $app -> request -> params('name');
		json($app->db->subscribe_to($feedurl, $name, null, true));
	});
	
	$app -> put('/casts/:id', function($id) use ($app) {
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

	$app->delete('/casts/:id', function($id) use($app) {
		$userid = $app->userid;
		$db_prefix = $GLOBALS['db_prefix'];
		$GLOBALS['dbh']->exec("DELETE FROM {$db_prefix}subscription WHERE castid=$id AND userid=$userid");
	});

	$app -> get('/events', function() use ($app) {
		include_once 'models/eventsresult.php';
		
		$episodeid = $app->request->params('episodeid');
		$since = $app->request->params('since');
		$limit = $app->request->params('limit');
		$exclude = $app->request->params('exclude');
		$exclude_self = $app->request->params('exclude_self') === 'true' ? true : false;
		
		if ($exclude != null){
			json(new eventsresult($app->db->get_events($episodeid, $since, $limit, $exclude, $exclude_self)));
		} else {
			json(new eventsresult($app->db->get_events($episodeid, $since, $limit, "70", $exclude_self)));
		}
	});

	$app -> post('/events', function() use ($app) {
		$db_prefix = $GLOBALS['db_prefix'];
		$receivedts = time();
				
		$json = json_decode($app->request->params('json'));

		foreach ($json as $event) {
			$sth = $GLOBALS['dbh']->prepare("INSERT INTO {$db_prefix}event (userid, type, episodeid, positionts, concurrentorder, clientts, receivedts, uniqueclientid) VALUES($app->userid, $event->type, $event->episodeid, $event->positionts, :concurrentorder, $event->clientts, $receivedts, $app->uniqueclientid)");
			$sth->bindParam(":concurrentorder", $event->concurrentorder, PDO::PARAM_INT);
			$sth->execute();
		}
	});

	$app -> get('/labels', function() use ($app) {
		$app->db->clean_labels();
		json($app->db->get_label(), true);
	});

	$app -> post('/labels', function() use ($app) {
		$name = $app -> request -> params('name');
		$content = $app -> request -> params('content');
		$expanded = $app -> request -> params('expanded');
		
		if (!(strpos($name,"label/") === 0)){
			$name = "label/" . $name; 
		}
		
		if (strlen($name) < (strlen("label/") + 1)){
			$app->halt(400, "Name to short");
		}
		
		$expanded = $expanded != "false";
		
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
			log_db_errors($sth);
			$labelid = $dbh->lastInsertId();
			
			$app->db->add_to_label_root("label/" . $labelid);
			json(array(
				"id" => $labelid,
				"name" => $name
			));
		} else {
			$app->halt(400, "Exsisting label");
		}
	});
	
	$app -> put('/labels/:id', function($id) use ($app) {
		$name = $app -> request -> params('name');
		$content = $app -> request -> params('content');
		$expanded = $app -> request -> params('expanded');
		
		$dbh = $GLOBALS['dbh'];
		$db_prefix = $GLOBALS['db_prefix'];
		$userid = $app -> userid;
		
		$sth = $dbh -> prepare("SELECT count(*)
			FROM {$db_prefix}label AS label
			WHERE
			label.labelid = :id
			AND label.name=\"root\"");
		$sth -> bindParam(":id",$id);
		$sth -> execute();
		$result = $sth -> fetchAll();
		$isroot = ($result["0"]["0"] >= 1);
				
		if ($name != null && !(strpos($name,"label/") === 0)){
			$name = "label/" . $name; 
		}
				
		if($expanded != null){
			$expanded = ($expanded == "true");
		}
		
		$query = "UPDATE {$db_prefix}label
				SET";
		$inputs = array();
		
		$wheres = 0;
		
		if(!$isroot && $name != null){
			$query .= " name = :name";
			$inputs[":name"] = $name;
			$wheres++;
		}
		
		if($content != null){
			if($wheres > 0){
				$query .= ",";
			}
			$query .= " content = :content";
			$inputs[":content"] = $content;
			$wheres++;
		}

		if(!$isroot && ($expanded !== null)){
			if($wheres > 0){ 
				$query .= ",";
			}
			$query .= " expanded = :expanded";
			$inputs[":expanded"] = $expanded;
		}
		
		$query .= " WHERE labelid = :id
			AND userid = :userid";
		$inputs[":id"] = $id;
		$inputs[":userid"] = $app->userid;
		
		$sth = $dbh -> prepare($query);
		$sth -> execute($inputs);
	});
	
	$app -> delete('/labels/:labelid', function($labelid) use ($app) {
		$app->db->del_label($labelid);
	});

});

$app -> run();
?>
