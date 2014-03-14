<?php
class DB {
	private $dbh;

	function __construct($dbh) {
		$this->dbh = $dbh;
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

	function get_casts($tag = null) {
		$casts = array();
		$app = $GLOBALS['app'];
		$db_prefix = $GLOBALS['db_prefix'];

		if ($tag == null) {
			$sth = $this->dbh->query("SELECT * FROM {$db_prefix}subscription WHERE userid=$app->userid");
		}
		else {
			$sth = $this->dbh->query("SELECT * FROM {$db_prefix}subscription WHERE find_in_set(binary '$tag', Tags) AND UserID=$app->userid");
		}
		if ($sth) {
			foreach ($sth as $row) {
				$feedid = $row['FeedID'];
				$tags = explode(',', $row['Tags']);

				$sth = $this->dbh->query("SELECT * FROM {$db_prefix}feed WHERE feedid=$feedid");
				if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
					array_push($casts, array_merge(array("castcloud" => array(
						"id" => $feedid, 
						"url" => $result['URL'], 
						"tags" => $tags)), $this->get_cast($feedid)));
				}
			}
		}

		return $casts;
	}

	function get_episodes($feedid, $since = null) {
		include_once 'models/episode.php';

		$episodes = array();
		$itemid = null;	
		$previtemid = null;
		$i = -1;

		$db_prefix = $GLOBALS['db_prefix'];
		if ($since == null) {
			$sth = $this->dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid");
		}
		else {
			$sth = $this->dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid AND crawlts > $since");
		}
		if ($result = $sth->fetchAll()) {
			foreach ($result as $row) {
				$itemid = $row['ItemID'];
				if ($itemid != $previtemid) {
					$i++;
				}

				if (startsWith($row['Location'], "channel/item")) {
					if (!isset($episodes[$i])) {
						$episodes[$i] = new episode($itemid, $feedid, null, array());
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

	function get_new_episodes($since) {
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
	}
	
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
}
?>