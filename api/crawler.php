<?php
include 'cc-settings.php';

$push_this = array();
GLOBAL $push_this;

$download_time = 0;
$parse_time = 0;
$crawl_time = 0;
$insert_time = 0;
$rows = 0;
GLOBAL $download_time;
GLOBAL $parse_time;
GLOBAL $crawl_time;
GLOBAL $insert_time;
GLOBAL $rows;

if (php_sapi_name() == "cli"){
	crawl_all();
}

function generateQuery($n) {
	$db_prefix = $GLOBALS['db_prefix'];
	$sql = "INSERT INTO {$db_prefix}feedcontent (castid, location, itemid, content, crawlts) VALUES";
	$sql.= implode(', ', array_fill(0, $n, '(?,?,?,?,?)'));
	return $sql;
}

function multiHTTP ($urlArr) { 
	$sockets = Array(); 
	$urlInfo = Array(); 
	$retDone = Array(); 
	$retData = Array(); 
	$errno   = Array(); 
	$errstr  = Array(); 
	for ($x=0;$x<count($urlArr);$x++) {
		try{
			$urlInfo[$x] = parse_url($urlArr[$x]); 
			$urlInfo[$x]["port"] = array_key_exists("port", $urlInfo[$x]) ? $urlInfo[$x]["port"] : 80;
			$urlInfo[$x]["path"] = array_key_exists("path", $urlInfo[$x]) ? $urlInfo[$x]["path"] : "/"; 
		
			$sockets[$x] = fsockopen($urlInfo[$x]["host"], $urlInfo[$x]["port"], 
				$errno[$x], $errstr[$x], 3);
			if ($sockets[$x]) {
				socket_set_blocking($sockets[$x], FALSE); 
				$query = array_key_exists("query",$urlInfo[$x]) ? "?" . $urlInfo[$x]["query"] : ""; 
				fputs($sockets[$x],"GET " . $urlInfo[$x]["path"] . "$query HTTP/1.0\r\nHost: " . 
					$urlInfo[$x]["host"] . "\r\n\r\n"); 
			}
		} catch (Exception $e){
			echo $urlArr[$x]." failed :(\n";
		} 
	}
	echo "Done opening ".sizeof($sockets)." sockets!\n\n";
	$done = false; 
	while (!$done) { 
		for ($x=0; $x < count($urlArr);$x++) {
			if ($sockets[$x]) {
				if (!feof($sockets[$x])) { 
					if (array_key_exists($x, $retData)) { 
						$retData[$x] .= fgets($sockets[$x],128); 
					} else { 
						$retData[$x] = fgets($sockets[$x],128); 
					} 
				} else {
					if (!array_key_exists($x, $retData)) {
						$retData[$x] = null;
					}
					$retDone[$x] = 1; 
				} 
			}
			else {
				$retData[$x] = null;
				$retDone[$x] = 1; 
			}
		} 
		usleep(100);
		$done = (array_sum($retDone) == count($urlArr)); 
	} 
	return $retData; 
}

function crawl_all() {
	include 'cc-settings.php';
	include_once 'util.php';
	$sth = $dbh->query("SELECT * FROM {$db_prefix}cast");
	if ($sth) {
		$urls = array();
		$xml = array();
		$GLOBALS['casts'] = array();
		foreach ($sth as $row) {
			array_push($urls, $row['URL']);
			array_push($xml, $row['XML']);
			array_push($GLOBALS['casts'], array("id" => $row['CastID'], "url" => $row['URL']));
		}
		$t = microtime(true);
		$feeds = multiHTTP($urls);
		$GLOBALS['download_time'] = microtime(true) - $t;
		$size_downloaded = 0;
		foreach($feeds as $feed) {
			$size_downloaded += strlen($feed);
		}
		$size_downloaded /= 1024 * 1024;
		echo "Downloaded ".sizeof($feeds)." feeds, $size_downloaded MB in ".$GLOBALS['download_time']." seconds\n";
		$i = 0;
		$sth = $dbh->prepare("UPDATE {$db_prefix}cast SET xml=? WHERE url=?");
		foreach ($feeds as $feed) {
			echo "#".($i + 1)." ".$urls[$i]."\n";
			if ($feed != null) {
				$data = substr($feed, strpos($feed, "\r\n\r\n") + 4);
				if (strcmp($xml[$i], $data) != 0) {
					//$sth->execute(array($data, $urls[$i]));
					echo "Crawling\n";
					crawl($urls[$i], $data);
				}
				else {
					echo "Skipping\n";
				}
			}
			else {
				echo "Feed was empty :(\n";
			}			
			$i++;
		}

		$push_this = $GLOBALS['push_this'];

		echo sizeof($push_this)." rows inserted\n";

		if (sizeof($push_this) > 0) {
			//$dbh->beginTransaction();
			$sth = $dbh->prepare(generateQuery(sizeof($push_this)));
			$vals = array();
			foreach ($push_this as $line) {
				array_push($vals, $line["castid"]);
				array_push($vals, $line["location"]);
				array_push($vals, $line["itemid"]);
				array_push($vals, $line["content"]);
				array_push($vals, $line["time"]);
			}
			$i = microtime(true) - $t;
			$t = microtime(true);
			$sth->execute($vals);
			//$dbh->commit();
		}

		echo "download ".$GLOBALS['download_time']." sec\nparse ".$GLOBALS['parse_time']." sec\ncrawl ".$GLOBALS['crawl_time']."sec\ninsert ".$GLOBALS['insert_time']." sec";
	}
}

function crawl_urls($urls) {
	include 'cc-settings.php';
	include_once 'util.php';
	$sth = $dbh->query("SELECT * FROM {$db_prefix}cast");
	if ($sth) {
		$db = $sth->fetchAll();
		$i = 0;
		$sth = $dbh->prepare("UPDATE {$db_prefix}cast SET xml=? WHERE url=?");
		for ($j = 0; $j < sizeof($urls); $j += 16) {
			//$t = microtime(true);
			$feeds = multiHTTP(array_slice($urls, $j, 16));
			//$GLOBALS['download_time'] += microtime(true) - $t;
			foreach ($feeds as $feed) {
				$data = substr($feed, strpos($feed, "\r\n\r\n") + 4);
				echo $urls[$i]."\n";
				$entry = where($db, "URL", $urls[$i]);
				if ($entry != null) {
					if (strcmp($entry["XML"], $data) != 0) {
						$sth->execute(array($data, $urls[$i]));
						crawl($urls[$i], $data);
					}
				}
				else {
					crawl($urls[$i], $data);
					$sth->execute(array($data, $urls[$i]));
				}
				
				$i++;
			}
		}
		//echo "download ".$GLOBALS['download_time']." sec\nparse ".$GLOBALS['parse_time']." sec\ncrawl ".$GLOBALS['crawl_time']." sec\ninsert ".$GLOBALS['insert_time']." sec";
	}
}

function where($arr, $k, $v) {
	foreach ($arr as $obj) {
		if ($obj[$k] == $v) {
			return $obj;
		}
	}
	return null;
}

function crawl($casturl, $data = null) {
	$dbh = $GLOBALS['dbh'];	
	$db_prefix = $GLOBALS['db_prefix'];
	$time = time();
	$castid = null;

	if ($data == null) {
		//$update_xml = $dbh->prepare("UPDATE {$db_prefix}cast SET xml=? WHERE url=?");
		$xml = simplexml_load_file($casturl, 'SimpleXMLElement', LIBXML_NOCDATA);
	}
	else {
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
	}

	if ($xml) {
		$sth = $dbh->query("SELECT * FROM {$db_prefix}cast WHERE url='$casturl'");
		$cast = $sth->fetch(PDO::FETCH_ASSOC);

		if ($cast) {
			$castid = $cast['CastID'];
			
			$dbh->exec("UPDATE {$db_prefix}cast SET crawlts=$time WHERE castid=$castid");
		}
		else {
			$dbh->exec("INSERT INTO {$db_prefix}cast (url, crawlts) VALUES('$casturl', $time)");
			$castid = $dbh->lastInsertId();
		}

		/*if ($data == null) {
			$xml_string = $xml->asXML();
			if (strcmp($xml_string, $cast['XML']) != 0) {
				$update_xml->execute(array($xml_string, $casturl));
			}
		}*/

		//$castid = where($GLOBALS['casts'], "url", $casturl)["id"];

		$o = json_decode(json_encode($xml->channel));
		$i = $o->item;
		unset($o->item);
		//echo json_encode($o);
		echo json_encode($i);

		/*$GLOBALS['push_this'] = array();

		$h = microtime(true) - $t;
		$t = microtime(true);*/

		//next_child($xml->channel, "channel/", $castid, $time);

		/*$c = microtime(true) - $t;
		$GLOBALS['crawl_time'] += $c;
		$t = microtime(true);

		$push_this = $GLOBALS['push_this'];

		echo sizeof($push_this)." rows inserted\n";

		$i = 0;
		if (sizeof($push_this) > 0) {
			//$dbh->beginTransaction();
			$sth = $dbh->prepare(generateQuery(sizeof($push_this)));
			$vals = array();
			foreach ($push_this as $line) {
				array_push($vals, $line["castid"]);
				array_push($vals, $line["location"]);
				array_push($vals, $line["itemid"]);
				array_push($vals, $line["content"]);
				array_push($vals, $line["time"]);
			}
			$i = microtime(true) - $t;
			$t = microtime(true);
			$sth->execute($vals);
			//$dbh->commit();
		}

		$i2 = microtime(true) - $t;
		
		$GLOBALS['insert_time'] += $i + $i2;
		echo "parsed for $d sec\nfetched castid for $h sec\ncrawled for $c sec\nbuilt query for $i sec\nexecuted query for $i2 sec\n\n";
	}
} catch (Exception $e) {}
*/
	}
	else {
		echo "Failed parsing XML :/\n";
	}
	return $castid;
}
?>
