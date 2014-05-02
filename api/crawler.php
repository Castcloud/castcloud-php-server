<?php
include 'cc-settings.php';

$push_this = array();
GLOBAL $push_this;

$download_time = 0;
$parse_time = 0;
$crawl_time = 0;
$insert_time = 0;
GLOBAL $download_time;
GLOBAL $parse_time;
GLOBAL $crawl_time;
GLOBAL $insert_time;

if (php_sapi_name() == "cli"){
	include 'cc-settings.php';
	include 'util.php';
	$sth = $dbh->query("SELECT * FROM {$db_prefix}cast");
	if ($sth) {
		$urls = array();
		foreach ($sth as $row) {
			array_push($urls, $row['URL']);
			//crawl($row['URL']);
		}
		$t = microtime(true);
		$feeds = multiHTTP($urls);
		$GLOBALS['download_time'] = microtime(true) - $t;
		$i = 0;
		foreach ($feeds as $feed) {
			crawl2($urls[$i], substr($feed, strpos($feed, "\r\n\r\n") + 4));
			$i++;
		}
		echo "download ".$GLOBALS['download_time']." sec\nparse ".$GLOBALS['parse_time']." sec\ncrawl ".$GLOBALS['crawl_time']."sec\ninsert ".$GLOBALS['insert_time']." sec";
	}
}

function generateQuery($n) {
	$db_prefix = $GLOBALS['db_prefix'];
	$sql = "INSERT INTO {$db_prefix}feedcontent (castid, location, itemid, content, crawlts) VALUES";
	$sql.= implode(', ', array_fill(0, $n, '(?,?,?,?,?)'));
	return $sql;
}

function multiHTTP ($urlArr) { 
 $sockets = Array(); // socket array! 
 $urlInfo = Array(); // info arr 
 $retDone = Array(); 
 $retData = Array(); 
 $errno   = Array(); 
 $errstr  = Array(); 
 for ($x=0;$x<count($urlArr);$x++) { 
  $urlInfo[$x] = parse_url($urlArr[$x]); 
  $urlInfo[$x][port] = ($urlInfo[$x][port]) ? $urlInfo[$x][port] : 80; 
  $urlInfo[$x][path] = ($urlInfo[$x][path]) ? $urlInfo[$x][path] : "/"; 
  $sockets[$x] = fsockopen($urlInfo[$x][host], $urlInfo[$x][port], 
                           $errno[$x], $errstr[$x], 30); 
  socket_set_blocking($sockets[$x],FALSE); 
  $query = ($urlInfo[$x][query]) ? "?" . $urlInfo[$x][query] : ""; 
  fputs($sockets[$x],"GET " . $urlInfo[$x][path] . "$query HTTP/1.0\r\nHost: " . 
        $urlInfo[$x][host] . "\r\n\r\n"); 
 } 
 // ok read the data from each one 
 $done = false; 
 while (!$done) { 
  for ($x=0; $x < count($urlArr);$x++) { 
   if (!feof($sockets[$x])) { 
    if ($retData[$x]) { 
     $retData[$x] .= fgets($sockets[$x],128); 
    } else { 
     $retData[$x] = fgets($sockets[$x],128); 
    } 
   } else { 
    $retDone[$x] = 1; 
   } 
  } 
  $done = (array_sum($retDone) == count($urlArr)); 
 } 
 return $retData; 
}

function crawl($casturl) {
	$dbh = $GLOBALS['dbh'];	
	$db_prefix = $GLOBALS['db_prefix'];
	$time = time();
	$castid = null;

	try {
		//echo "downloading $casturl\n";
		$t = microtime(true);
		$xml = simplexml_load_file($casturl);

		$sth = $dbh->query("SELECT * FROM {$db_prefix}cast WHERE url='$casturl'");
		if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
			$castid = $result['CastID'];
			
			$dbh->exec("UPDATE {$db_prefix}cast SET crawlts=$time");
		}
		else {
			$dbh->exec("INSERT INTO {$db_prefix}cast (url, crawlts) VALUES('$casturl', $time)");
			$castid = $dbh->lastInsertId();
		}

		$GLOBALS['push_this'] = array();

		$d = microtime(true) - $t;
		$GLOBALS['download_time'] += $d;
		$t = microtime(true);
		//echo "crawling $casturl\n";

		next_child($xml->channel, "channel/", $castid, $time);

		$c = microtime(true) - $t;
		$GLOBALS['crawl_time'] += $c;
		$t = microtime(true);
		//echo "inserting $casturl\n";

		$push_this = $GLOBALS['push_this'];

		if (sizeof($push_this) > 0) {
			$dbh->beginTransaction();
			$sth = $dbh->prepare(generateQuery(sizeof($push_this)));
			$vals = array();
			foreach ($push_this as $line) {
				array_push($vals, $line["castid"]);
				array_push($vals, $line["location"]);
				array_push($vals, $line["itemid"]);
				array_push($vals, $line["content"]);
				array_push($vals, $line["time"]);
			}
			$sth->execute($vals);
			$dbh->commit();
		}

		$i = microtime(true) - $t;
		$GLOBALS['insert_time'] += $i;
		//echo "downloaded for $d sec\ncrawled for $c sec\ninserted for $i sec\n\n";
	} catch (Exception $e) {}

	return $castid;
}

function crawl2($casturl, $data) {
	$dbh = $GLOBALS['dbh'];	
	$db_prefix = $GLOBALS['db_prefix'];
	$time = time();
	$castid = null;

	try {
		//echo "downloading $casturl\n";
		$t = microtime(true);
		$xml = simplexml_load_string($data);

		$sth = $dbh->query("SELECT * FROM {$db_prefix}cast WHERE url='$casturl'");
		if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
			$castid = $result['CastID'];
			
			$dbh->exec("UPDATE {$db_prefix}cast SET crawlts=$time");
		}
		else {
			$dbh->exec("INSERT INTO {$db_prefix}cast (url, crawlts) VALUES('$casturl', $time)");
			$castid = $dbh->lastInsertId();
		}

		$GLOBALS['push_this'] = array();

		$d = microtime(true) - $t;
		$GLOBALS['parse_time'] += $d;
		$t = microtime(true);
		//echo "crawling $casturl\n";

		next_child($xml->channel, "channel/", $castid, $time);

		$c = microtime(true) - $t;
		$GLOBALS['crawl_time'] += $c;
		$t = microtime(true);
		//echo "inserting $casturl\n";

		$push_this = $GLOBALS['push_this'];

		if (sizeof($push_this) > 0) {
			$dbh->beginTransaction();
			$sth = $dbh->prepare(generateQuery(sizeof($push_this)));
			$vals = array();
			foreach ($push_this as $line) {
				array_push($vals, $line["castid"]);
				array_push($vals, $line["location"]);
				array_push($vals, $line["itemid"]);
				array_push($vals, $line["content"]);
				array_push($vals, $line["time"]);
			}
			$sth->execute($vals);
			$dbh->commit();
		}

		$i = microtime(true) - $t;
		$GLOBALS['insert_time'] += $i;
		echo "parsed for $d sec\ncrawled for $c sec\ninserted for $i sec\n\n";
	} catch (Exception $e) {}

	return $castid;
}

function next_child($node, $url, $castid, $time) {
	foreach ($node->children() as $child) {
		process_child($child, null, $url, $castid, $time);
	}

	foreach ($node->getDocNamespaces() as $ns => $nsurl) {
		foreach ($node->children($nsurl) as $child) {
			process_child($child, $ns, $url, $castid, $time);
		}
	}
}

function process_child($child, $ns, $url, $castid, $time) {
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

		$sth = $dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE castid=$castid AND location='$newurl'");
		if ($sth && $sth->rowCount() > 0) {
			return;
		}
	}

	if ($child->count() > 0) {
		if ($child->getName() == "item") {
			$item = true;
		}
		next_child($child, $newurl."/", $castid, $time);
	}
	else {
		if ($child->getName() == "guid") {
			$item = false;

			$sth = $dbh->query("SELECT * FROM {$db_prefix}feedcontent WHERE location='channel/item/guid' AND content='$child' AND castid=$castid");
			if ($sth && $sth->rowCount() > 0) {
				// Existing item
			}
			else {
				$dbh->exec("INSERT INTO {$db_prefix}itemid () VALUES()");
				$itemid = $dbh->lastInsertId();

				foreach ($buffer as $line) {
					array_push($GLOBALS['push_this'], array("castid" => $castid, "location" => $line["location"], "itemid" => $itemid, "content" => $line["content"], "time" => $time));
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
				array_push($GLOBALS['push_this'], array("castid" => $castid, "location" => $newurl, "itemid" => $itemid, "content" => (string)$child, "time" => $time));

				foreach ($child->attributes() as $key => $value) {
					array_push($GLOBALS['push_this'], array("castid" => $castid, "location" => "$newurl/$key", "itemid" => $itemid, "content" => $value, "time" => $time));
				}
			}
		}
	}
}
?>
