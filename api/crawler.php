<?php
include 'cc-settings.php';

$push_this = array();
GLOBAL $push_this;

if (isset($_GET['t'])){
	include 'cc-settings.php';
	if ($_GET['t'] == $crawl_token) {
		include 'util.php';
		$sth = $dbh->query("SELECT * FROM {$db_prefix}feed");
		if ($sth) {
			foreach ($sth as $row) {
				crawl($row['URL']);
			}
		}
	}
}

function generateQuery($n) {
	$db_prefix = $GLOBALS['db_prefix'];
	$sql = "INSERT INTO {$db_prefix}feedcontent (feedid, location, itemid, content, crawlts) VALUES";
	$sql.= implode(', ', array_fill(0, $n, '(?,?,?,?,?)'));
	return $sql;
}

function crawl($feedurl) {
	$dbh = $GLOBALS['dbh'];	
	$db_prefix = $GLOBALS['db_prefix'];
	$time = time();
	$xml = simplexml_load_file($feedurl);

	$sth = $dbh->query("SELECT * FROM {$db_prefix}feed WHERE url='$feedurl'");
	if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
		$feedid = $result['FeedID'];
		
		$dbh->exec("UPDATE {$db_prefix}feed SET crawlts=$time");
	}
	else {
		$dbh->exec("INSERT INTO {$db_prefix}feed (url, crawlts) VALUES('$feedurl', $time)");
		$feedid = $dbh->lastInsertId();
	}

	next_child($xml->channel, "channel/", $feedid, $time);

	$push_this = $GLOBALS['push_this'];

	$dbh->beginTransaction();
	$sth = $dbh->prepare(generateQuery(sizeof($push_this)));
	$vals = array();
	foreach ($push_this as $line) {
		array_push($vals, $line["feedid"]);
		array_push($vals, $line["location"]);
		array_push($vals, $line["itemid"]);
		array_push($vals, $line["content"]);
		array_push($vals, $line["time"]);
	}
	$sth->execute($vals);
	$dbh->commit();

	return $feedid;
}

function next_child($node, $url, $feedid, $time) {
	foreach ($node->children() as $child) {
		process_child($child, null, $url, $feedid, $time);
	}

	foreach ($node->getDocNamespaces() as $ns => $nsurl) {
		foreach ($node->children($nsurl) as $child) {
			process_child($child, $ns, $url, $feedid, $time);
		}
	}
}

function process_child($child, $ns, $url, $feedid, $time) {
	static $item = false;
	static $buffer = array();
	static $itemid = null;
	$dbh = $GLOBALS['dbh'];
	$db_prefix = $GLOBALS['db_prefix'];
	if ($ns != null) {
		$newurl = $url.$ns.":".$child->getName();
	}
	else {
		$newurl = $url.$child->getName();
	}

	if (!startsWith($url, "channel/item")) {
		$itemid = null;

		$sth = $dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid AND location='$newurl'");
		if ($sth && $sth->rowCount() > 0) {
			return;
		}
	}

	if ($child->count() > 0) {
		if ($child->getName() == "item") {
			$item = true;
		}
		next_child($child, $newurl."/", $feedid, $time);
	}
	else {
		if ($child->getName() == "guid") {
			$item = false;

			$sth = $dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE location='channel/item/guid' AND content='$child' AND feedid=$feedid");
			if ($sth && $sth->rowCount() > 0) {
				// Existing item
			}
			else {
				$dbh->exec("INSERT INTO {$db_prefix}itemid () VALUES()");
				$itemid = $dbh->lastInsertId();

				foreach ($buffer as $line) {
					//push_line($feedid, $line["location"], $itemid, $line["content"], $time);
					//array_push($push_this, array($feedid, $line["location"], $itemid, $line["content"], $time));
					array_push($GLOBALS['push_this'], array("feedid" => $feedid, "location" => $line["location"], "itemid" => $itemid, "content" => $line["content"], "time" => $time));
				}
				$buffer = array();
			}
		}


		if ($item) {
			array_push($buffer, array("location" => $newurl, "content" => (string)$child));

			foreach ($child->attributes() as $key => $value) {
				array_push($buffer, array("location" => "$newurl/$key", "content" => $value));
			}
		}
		else {
			if (!($itemid == null && startsWith($newurl, "channel/item"))) {
				//push_line($feedid, $newurl, $itemid, (string)$child, $time);
				//array_push($push_this, array($feedid, $newurl, $itemid, (string)$child, $time));
				array_push($GLOBALS['push_this'], array("feedid" => $feedid, "location" => $newurl, "itemid" => $itemid, "content" => (string)$child, "time" => $time));

				foreach ($child->attributes() as $key => $value) {
					//push_line($feedid, "$newurl/$key", $itemid, $value, $time);
					//array_push($push_this, array($feedid, "$newurl/$key", $itemid, $value, $time));
					array_push($GLOBALS['push_this'], array("feedid" => $feedid, "location" => "$newurl/$key", "itemid" => $itemid, "content" => $value, "time" => $time));
				}
			}
		}
	}
}

function push_line($feedid, $location, $itemid, $content, $time) {
	$sth = $GLOBALS['insert_sth'];
	$sth->bindParam(':feedid', $feedid, PDO::PARAM_INT);
	$sth->bindParam(':location', $location, PDO::PARAM_STR);
	$sth->bindParam(':itemid', $itemid, PDO::PARAM_INT);
	$sth->bindParam(':content', $content, PDO::PARAM_STR);
	$sth->bindParam(':time', $time, PDO::PARAM_INT);
	$sth->execute();
}

function crawler_get_cast($feedid) {
	$db_prefix = $GLOBALS['db_prefix'];
	$cast = array();
	$sth = $GLOBALS['dbh']->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid");
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

function crawler_get_casts($tag = null) {
	$casts = array();
	$app = $GLOBALS['app'];
	$db_prefix = $GLOBALS['db_prefix'];
	$dbh = $GLOBALS['dbh'];
	if ($tag == null) {
		$sth = $dbh -> query("SELECT * FROM {$db_prefix}subscription WHERE userid=$app->userid");
	}
	else {
		$sth = $dbh -> query("SELECT * FROM {$db_prefix}subscription WHERE find_in_set(binary '$tag', Tags) AND UserID=$app->userid");
	}
	if ($sth) {
		foreach ($sth as $row) {
			$feedid = $row['FeedID'];
			$tags = explode(',', $row['Tags']);

			$sth = $dbh->query("SELECT * FROM {$db_prefix}feed WHERE feedid=$feedid");
			if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
				array_push($casts, array_merge(array("castcloud" => array(
					"id" => $feedid, 
					"url" => $result['URL'], 
					"tags" => $tags)), crawler_get_cast($feedid)));
			}
		}
	}

	return $casts;
}

function crawler_get_episodes($feedid, $since = null) {
	include_once 'models/episode.php';

	$episodes = array();
	$itemid = null;	
	$previtemid = null;
	$i = -1;

	$db_prefix = $GLOBALS['db_prefix'];
	if ($since == null) {
		$sth = $GLOBALS['dbh']->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid");
	}
	else {
		$sth = $GLOBALS['dbh']->query("SELECT * FROM {$db_prefix}feedcontent WHERE feedid=$feedid AND crawlts > $since");
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

					$sth = $GLOBALS['dbh']->query("SELECT * FROM {$db_prefix}event WHERE itemid=$itemid ORDER BY clientts LIMIT 1");
					if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
						$episodes[$i]->lastevent = array();
						$episodes[$i]->lastevent["type"] = $result["Type"];
						$episodes[$i]->lastevent["positionts"] = $result["PositionTS"];
						$episodes[$i]->lastevent["clientts"] = $result["ClientTS"];
						$episodes[$i]->lastevent["clientname"] = $GLOBALS['app']->clientname;
						$episodes[$i]->lastevent["clientdescription"] = $GLOBALS['app']->clientdescription;
					}
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

function crawler_get_new_episodes($since) {
	$episodes = array();
	$userid = $GLOBALS['app']->userid;

	$db_prefix = $GLOBALS['db_prefix'];
	$sth = $GLOBALS['dbh']->query("SELECT * FROM {$db_prefix}subscription WHERE userid=$userid");
	if ($result = $sth->fetchAll()) {
		foreach ($result as $row) {
			$episodes = array_merge($episodes, crawler_get_episodes($row['FeedID'], $since));
		}
	}

	return $episodes;
}
?>
