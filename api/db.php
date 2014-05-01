<?php
date_default_timezone_set('UTC');
$current;
GLOBAL $current;
function cmp($a, $b) {
	return strtotime($GLOBALS['current'][$a]->feed["pubDate"]) < strtotime($GLOBALS['current'][$b]->feed["pubDate"]);
}

class DB {
	private $dbh;
	private $db_prefix;

	function __construct($dbh) {
		$this->dbh = $dbh;
		$this->db_prefix = $GLOBALS['db_prefix'];
	}
	
	function get_casts() {
		include_once 'models/cast.php';
		$userid = $GLOBALS['app']->userid;

		$query = "SELECT
			cast.CastID AS id,
			subs.name,
			cast.url,
			subs.arrangement
			FROM 
			{$this->db_prefix}cast AS cast,
			{$this->db_prefix}subscription AS subs
			WHERE
			subs.userid=:userid 
			AND subs.CastID = cast.CastID";
		$inputs = array(":userid" => $userid);
		
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare($query);
		$sth->execute($inputs);
		
		$casts = $sth->fetchAll(PDO::FETCH_CLASS, "cast");
		
		foreach ($casts as &$cast) {
			$cast->feed = $this->get_cast($cast->id);
		}
		
		return $casts;
	}

	function get_cast($castid) {//
		$cast = array();
		$sth = $this->dbh->query("SELECT * FROM {$this->db_prefix}feedcontent WHERE CastID=$castid");
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
	
	/**
	 * Cleans up all the users labels
	 * Generate first to get all the info we need
	 * Delete second since the information from the begunning is still fresh
	 * Add third since this affects the information we have generate irreversably
	 */
	function clean_Labels(){
		// Lets gather up all the stuff
		$labels = $this->get_label();
		$subs = $this->get_casts();
		
		/*
		 * GENERATE
		 */
		// All the CastIDs from the subscriptions 
		$allcastsinsubs = array();
		foreach ($subs as $cast) {
			$allcastsinsubs[] = $cast->id;
		}
		// All the true LabelIDs
		$alllabelids = array();
		// All the LabelIDs from root
		$labelsinroot = array();
		// All the casts that is in any label
		$allcastsinlabel = array();
		// Look throug all labels to fill up the arrays
		foreach ($labels as $label) {
			// All the individual enteries inside the label
			$content = superexplode($label->content);
			foreach ($content as $contentitem) {
				// If it is a cast, but them in the array
				if (startsWith($contentitem, "cast/")){
					$allcastsinlabel[] = contentAfter($contentitem, "cast/");
				}
			}
			if($label->root){
				// Lets get the labels inside the root label
				foreach ($content as $contentitem) {
					if (startsWith($contentitem, "label/")){
						$labelsinroot[] = contentAfter($contentitem, "label/");
					}
				}
			} else {
				// Take note of all LabelIDs that are not root
				$alllabelids[] = $label->id;
			}
		}
		
		/*
		 * DELETE
		 */
		// Anything inside any labels that does not actually exist
		$removefromlabels = array();
		// Casts that are inside a label but not subscribed too
		$nonexsistingcasts = array_diff($allcastsinlabel,$allcastsinsubs);
		foreach ($nonexsistingcasts as $castid) {
			$removefromlabels[] = "cast/" . $castid;
		}
		// Labels that are inside root but does not exsist
		$nonexsistinglables = array_diff($labelsinroot,$alllabelids);
		foreach ($nonexsistinglables as $labelid) {
			$removefromlabels[] = "label/" . $labelid;
		}
		
		// No need to bother the database unless there are actuall changes
		if (!empty($removefromlabels)){
			// Now its time to rewrite all the labels while removing nonexsisten stuff
			$query = "UPDATE {$this->db_prefix}label
				SET content = :content
				WHERE labelid = :id
				AND userid = :userid";
			$dbh = $GLOBALS['dbh'];
			$sth = $dbh -> prepare($query);
			$userid = $GLOBALS['app']->userid;
			foreach ($labels as $label) {
				$content = $label->content;
				foreach ($removefromlabels as $removee) {
					$content = str_replace($removee, "", $content);
				}
				$content = implode(",",superexplode($content));
				
				$inputs = array();
				$inputs[":content"] = $content;
				$inputs[":id"] = $label->id;
				$inputs[":userid"] = $userid;
				
				$sth -> execute($inputs);
			}
		}
		
		/*
		 * ADD
		 */
		// What root is missing
		$addtoroot = "";
		// Lets find casts that are not in any label
		$castsnotinlabels = array_diff($allcastsinsubs,$allcastsinlabel);
		if (!empty($castsnotinlabels)){
			$addtoroot .= "cast/" . implode(",cast/", $castsnotinlabels);
		}
		// Lets find labels that are not in root
		$labelssnotinrootlabel = array_diff($alllabelids,$labelsinroot);
		if (!empty($labelssnotinrootlabel)){
			if($addtoroot != ""){
				$addtoroot .= ",";
			}
			$addtoroot .= "label/" . implode(",label/", $labelssnotinrootlabel);
		}
		if($addtoroot != ""){
			// Put them into root
			$this->add_to_label_root($addtoroot);
		}
	}
	
	function get_label($name = null, $labelid = null) {
		include_once 'models/label.php';
		$userid = $GLOBALS['app']->userid;

		$query = "SELECT
			label.LabelID AS id,
			label.name,
			label.content,
			label.expanded
			FROM 
			{$this->db_prefix}label AS label
			WHERE
			label.userid = :userid";
		$inputs = array(":userid" => $userid);
		
		if ($name != null){
			$query .= " AND label.name = :name";
			$inputs[":name"] = $name;
		}
		
		if ($labelid != null){
			$query .= " AND label.labelid = :labelid";
			$inputs[":labelid"] = $labelid;
		}
		
		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare($query);
		$sth->execute($inputs);
		
		$label = $sth->fetchAll(PDO::FETCH_CLASS, "label");
		
		return $label;
	}

	function add_to_label_root($value){
		$dbh = $GLOBALS['dbh'];
		$userid = $GLOBALS['app']->userid;
		
		$root = $this->get_label("root");
		
		if(empty($root)){
			$sth = $dbh -> prepare("INSERT INTO {$this->db_prefix}label
				(userid, name, content, expanded) 
				VALUES($userid, 'root', :content, TRUE)");
			$sth -> bindParam(":content",$value);
			$sth -> execute();
			return;
		}
		
		$root = $root[0];
				
		$root->content .= "," . $value;
		
		$sth = $dbh -> prepare("UPDATE {$this->db_prefix}label
			SET content = :content
			WHERE LabelID = :id");
		$sth -> bindParam(":content",$root->content);
		$sth -> bindParam(":id",$root->id);
		$sth -> execute();
	}

	function get_episodes($castid, $labelid, $exclude = "70", $since = null, $episode = null) {
		include_once 'models/episode.php';
		
		$label = null;
		$userid = $GLOBALS['app']->userid;
		
		if ($labelid != null){
			$label = $this->get_label(null, $labelid);
			if($label < 1){
				// Label most likely unvalid labelid
				return array();
			}
			$label = superexplode($label[0]->content);
		}
		
		$exclude = superexplode($exclude);
		$inputs = array();
		
		$query = "SELECT
			feed.CastID,
			feed.Location,
			feed.ItemID,
			feed.Content
			FROM
			{$this->db_prefix}feedcontent AS feed
			LEFT JOIN 
				{$this->db_prefix}subscription AS subs
				ON subs.CastID = feed.CastID
				AND subs.UserID = :userid
			LEFT JOIN 
				{$this->db_prefix}event AS event
				ON feed.ItemID = event.ItemID";
		if (!empty($exclude)){
			$query .= " AND (";
			for ($i = 0; $i < count($exclude); $i++) {
				if ($i != 0){
					$query .= " OR";
				}
				$query .= " event.TYPE = :exclude" . $i;
				$inputs[":exclude" . $i] = $exclude[$i];
				
				$query .= " AND ReceivedTS = (SELECT MAX(ReceivedTS)
          			FROM {$this->db_prefix}event AS ev2
         			WHERE ev2.ItemID = event.ItemID
         			AND ev2.UserID = :userid)";
			} 
			$query .= " )";
		}
		$query .= " AND event.UserID = :userid
			WHERE subs.CastID IS NOT NULL";
		$inputs[":userid"] = $userid;
		
		if ($label != null){
			$query .= " AND (";
			for ($i = 0; $i < count($label); $i++) {
				if (startsWith($label[$i], "cast/")){
					if ($i != 0){
						$query .= " OR";
					}
					$query .= " feed.castid = :castid" . $i;
					$inputs[":castid" . $i] = substr($label[$i], strlen("cast/"));
				}
			} 
			$query .= " )";
		}
		
		if (!empty($exclude)) {
			$query.=" AND event.ItemID IS NULL";
		}

		if ($since != null) {
			$query.=" AND feed.crawlts > :since";
			$inputs[":since"] = $since;
		}

		if ($castid != null) {
			$query.=" AND feed.CastID = :castid";
			$inputs[":castid"] = $castid;
		}
		
		if ($episode != null) {
			$query.=" AND feed.ItemID = :itemid";
			$inputs[":itemid"] = $episode;
		}
		
		//var_dump($query,$inputs);
		
		$sth = $this->dbh->prepare($query);
		$sth->execute($inputs);
		
		//var_dump($sth->errorInfo());
		
		$episodes = array();
		$itemid = null;
		$previtemid = null;
		$i = -1;
		if ($result = $sth->fetchAll()) {
			foreach ($result as $row) {
				$itemid = $row['ItemID'];
				$castid = $row['CastID'];
				if (startsWith($row['Location'], "channel/item")) {
					if ($itemid != $previtemid) {
						$i++;
					}
				
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

		$GLOBALS['current'] = $episodes;
		uksort($episodes, 'cmp');

		return array_values($episodes);
	}	

	function get_events($itemid, $since, $limit = null) {
		include_once 'models/event.php';
		$userid = $GLOBALS['app']->userid;

		$query = "SELECT
			event.type,
			event.itemid AS episodeid,
			event.positionts,
			event.clientts,
			event.concurrentorder, 
			client.name AS clientname,
			clientauthorization.clientdescription
			FROM 
			{$this->db_prefix}event AS event,
			{$this->db_prefix}clientauthorization AS clientauthorization,
			{$this->db_prefix}client AS client
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
			$query.=" LIMIT :limit";
			$inputs[":limit"] = $limit;
		}
		
		$dbh = $GLOBALS['dbh'];
		$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
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

		$query = "SELECT
			setting.settingid,
			setting.setting,
			setting.value,
			setting.ClientID IS NOT NULL AS clientspesific
			FROM 
			{$this->db_prefix}setting AS setting
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
}
?>