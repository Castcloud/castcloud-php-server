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

	if (sizeof($push_this) > 0) {
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
	}

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
				array_push($GLOBALS['push_this'], array("feedid" => $feedid, "location" => $newurl, "itemid" => $itemid, "content" => (string)$child, "time" => $time));

				foreach ($child->attributes() as $key => $value) {
					array_push($GLOBALS['push_this'], array("feedid" => $feedid, "location" => "$newurl/$key", "itemid" => $itemid, "content" => $value, "time" => $time));
				}
			}
		}
	}
}
?>
