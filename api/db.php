<?php
class DB {
	private $dbh;

	function __construct($dbh) {
		$this->dbh = $dbh;
	}
	
	function get_casts($tag = null) {
		include_once 'models/cast.php';
		$userid = $GLOBALS['app']->userid;

		$db_prefix = $GLOBALS['db_prefix'];
		$query = "SELECT
			cast.FeedID AS id,
			subs.name,
			cast.url,
			subs.tags,
			subs.arrangement
			FROM 
			{$db_prefix}feed AS cast,
			{$db_prefix}subscription AS subs
			WHERE
			subs.userid=:userid 
			AND subs.FeedID = cast.FeedID";
		$inputs = array(":userid" => $userid);

		if ($tag != null) {
			$query.=" AND find_in_set(binary :tag, Tags)";
			$inputs[":tag"] = $tag;
		}
		
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare($query);
		$sth->execute($inputs);

		if (!$sth){
			exit;
		}
		$casts = $sth->fetchAll(PDO::FETCH_CLASS, "cast");
		
		foreach ($casts as &$cast) {
			$cast->feed = $this->get_cast($cast->id);
		}
		
		return $casts;
	}

	function get_cast($feedid) {
		$db_prefix = $GLOBALS['db_prefix'];
		$cast = array();
		$sth = $this->dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid");
		if ($result = $sth->fetchAll()) {
			$needsLove = null;
			foreach ($result as $row) {
				if (!startsWith($row['Location'], "channel/item")) {
					$exploded = explode("/", $row['Location']);
					if (sizeof($exploded) > 2) {
						if ($needsLove != null) {
							if ($exploded[1] == $needsLove) {
								$v = $cast[$needsLove];
								$cast[$needsLove] = array();
								$cast[$needsLove][$needsLove] = $v; 
							}
							$needsLove = null;
						}
						$cast[$exploded[1]][$exploded[2]] = $row['Content'];
					}
					else {
						if ($row["Content"] != "") {
							$needsLove = $exploded[1];
						}
						$cast[$exploded[1]] = $row['Content'];
					}
				}
			}
		}

		return $cast;
	}

	function get_episodes($castid, $tag, $exclude = "70", $since = null) {
		include_once 'models/episode.php';
		
		$userid = $GLOBALS['app']->userid;
		
		// explode and clear " " and ""
		$exclude = explode(',', $exclude);
		$exclude = array_map('trim', $exclude);
		$exclude = array_diff($exclude, array(''));
		$inputs = array();
		
		$db_prefix = $GLOBALS['db_prefix'];
		$query = "SELECT
			feed.FeedID,
			feed.Location,
			feed.ItemID,
			feed.Content
			FROM
			{$db_prefix}feedcontent AS feed
			LEFT JOIN 
				{$db_prefix}event AS event
				ON feed.ItemID = event.ItemID";
		if (!empty($exclude)){
			$query .= " AND (";
			for ($i = 0; $i < count($exclude); $i++) {
				if ($i != 0){
					$query .= " OR";
				}
				$query .= " event.TYPE = :exclude" . $i;
				$inputs[":exclude" . $i] = $exclude[$i];
			} 
			$query .= " )";
		}
		$query .= " AND event.UserID = :userid
			LEFT JOIN 
				{$db_prefix}subscription AS subs
				ON subs.FeedID = feed.FeedID
			WHERE
			event.ItemID IS NULL";
		$inputs[":userid"] = $userid;
		
		if ($tag != null){
			$query.= " AND find_in_set(binary :tag, subs.Tags)";
			$inputs[":tag"] = $tag;
		}

		if ($since != null) {
			$query.=" AND feed.crawlts > :since";
			$inputs[":since"] = $since;
		}

		if ($castid != null) {
			$query.=" AND feed.FeedID = :castid";
			$inputs[":castid"] = $castid;
		}
		
		$sth = $this->dbh->prepare($query);
		$sth->execute($inputs);
		
		$episodes = array();
		$itemid = null;
		$previtemid = null;
		$i = -1;
		if ($result = $sth->fetchAll()) {
			foreach ($result as $row) {
				$itemid = $row['ItemID'];
				$castid = $row['FeedID'];
				if ($itemid != $previtemid) {
					$i++;
				}

				if (startsWith($row['Location'], "channel/item")) {
					if (!isset($episodes[$i])) {
						$episodes[$i] = new episode($itemid, $castid, null, array());
						$episodes[$i]->lastevent = $this->get_events($itemid, null, 1);
					}

					$exploded = explode("/", $row['Location']);
					if (sizeof($exploded) > 3) {
						$episodes[$i]->feed[$exploded[2]][$exploded[3]] = $row['Content'];
					}
					else {
						if ($exploded[2] == "guid") {
							$episodes[$i]->feed["guid"]["guid"] = $row['Content'];
						}
						else {
							$episodes[$i]->feed[$exploded[2]] = $row['Content'];
						}
					}
				}
				$previtemid = $itemid;
			}
		}

		return $episodes;
	}

	/*function get_new_episodes($since) {
		$episodes = array();
		$userid = $GLOBALS['app']->userid;

		$db_prefix = $GLOBALS['db_prefix'];
		$sth = $this->dbh->query("SELECT * FROM {$db_prefix}subscription WHERE userid=$userid");
		if ($result = $sth->fetchAll()) {
			foreach ($result as $row) {
				$episodes = array_merge($episodes, $this->get_episodes($row['FeedID'], $since));
			}
		}

		return $episodes;
	}*/
	
	function get_events($itemid, $since, $limit = null) {
		include_once 'models/event.php';
		$userid = $GLOBALS['app']->userid;

		$db_prefix = $GLOBALS['db_prefix'];
		$query = "SELECT
			event.type,
			event.itemid AS episodeid,
			event.positionts,
			event.clientts,
			event.concurrentorder, 
			client.name AS clientname,
			clientauthorization.clientdescription
			FROM 
			{$db_prefix}event AS event,
			{$db_prefix}clientauthorization AS clientauthorization,
			{$db_prefix}client AS client
			WHERE
			event.userid=:userid 
			AND event.UniqueClientID = clientauthorization.UniqueClientID
			AND clientauthorization.ClientID = client.ClientID";
		$inputs = array(":userid" => $userid);

		if ($itemid != null) {
			$query.=" AND event.itemid=:itemid";
			$inputs[":itemid"] = $itemid;
		}
		if ($since != null) {
			$query.=" AND event.receivedts >= :since";
			$inputs[":since"] = $since;
		}

		$query.= " ORDER BY
			event.clientts DESC,
			event.concurrentorder DESC";
		
		if ($limit != null) {
			/*
			 * May the heavens have mercy on the fool that gets limits from user imput
			 */
			$query.=" LIMIT {$limit}";
		}
		
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare($query);
		$sth->execute($inputs);

		if ($sth) {
			if ($limit == "1" && $sth->rowCount() == 1){
				$events = $sth->fetchAll(PDO::FETCH_CLASS, "event");
				return $events[0];
			} elseif ($limit == "1" && $sth->rowCount() < 1){
				return null;
			} else {
				return $sth->fetchAll(PDO::FETCH_CLASS, "event");
			}
		}
	}
	
	function get_settings() {
		include_once 'models/setting.php';
		$userid = $GLOBALS['app']->userid;
		$clientid = $GLOBALS['app']->clientid;

		$db_prefix = $GLOBALS['db_prefix'];
		$query = "SELECT
			setting.settingid,
			setting.setting,
			setting.value,
			setting.ClientID IS NOT NULL AS clientspesific
			FROM 
			{$db_prefix}setting AS setting
			WHERE
			setting.userid=:userid
			AND (setting.ClientID = :clientid
			OR setting.ClientID IS NULL)";
		$inputs = array(":userid" => $userid,
			":clientid" => $clientid);

		
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare($query);
		$sth->execute($inputs);

		if ($sth) {
			return $sth->fetchAll(PDO::FETCH_CLASS, "setting");
		}
	}
	
	function get_opml(){
		include_once 'models/opml.php';
		$app = $GLOBALS['app'];
		$casts = $this->get_casts();
		$opml = new opml();
		$tags = array();
		$opml->title = "Castcloud opml export";
		$opml->dateCreated = date("r", time());
		$opml->ownerName = $app->username;
		$opml->ownerEmail = $app->mailaddress;
		foreach ($casts as &$cast){
			if(empty($cast->tags)){
				$cast->tags = array("Untagged");
			}
			$tags = array_merge($tags, $cast->tags);
		}
		$tags = array_unique($tags);
		foreach ($tags as $tagName) {
			$opmltag = new opml_tag();
			$opmltag->title = $tagName;
			foreach ($casts as $cast) {
				if (in_array($tagName, $cast->tags)){
					$opmlcast = new opml_cast();
					$opmlcast->title = $cast->feed["title"];
					$opmlcast->url = $cast->url;
					if (!empty($cast->feed["description"])){
						$opmlcast->description = $cast->feed["description"];
					} else {
						$opmlcast->description = "";
					}
					$opmltag->casts[]=$opmlcast;
				}
			}
			$opml->tags[] = $opmltag;
		}
		return $opml;
	}
}
?>