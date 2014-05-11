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
	$sql = "INSERT INTO {$db_prefix}episode (castid, content, guid, crawlts) VALUES";
	$sql.= implode(', ', array_fill(0, $n, '(?,?,?,?)'));
	return $sql;
}

function multiHTTP ($urlArr) { 
	$sockets = Array(); 
	$urlInfo = Array(); 
	$retDone = Array(); 
	$retData = Array(); 
	$errno   = Array(); 
	$errstr  = Array();

	$user_agent = "Castcloud crawler; +https://github.com/castcloud/castcloud (PHP ".phpversion().")";

	for ($x=0;$x<count($urlArr);$x++) {
		try{
			$urlInfo[$x] = parse_url($urlArr[$x]); 
			$urlInfo[$x]["port"] = array_key_exists("port", $urlInfo[$x]) ? $urlInfo[$x]["port"] : 80;
			$urlInfo[$x]["path"] = array_key_exists("path", $urlInfo[$x]) ? $urlInfo[$x]["path"] : "/"; 
		
			$sockets[$x] = fsockopen($urlInfo[$x]["host"], $urlInfo[$x]["port"], 
				$errno[$x], $errstr[$x], 1);
			if ($sockets[$x]) {
				socket_set_blocking($sockets[$x], FALSE); 
				$query = array_key_exists("query",$urlInfo[$x]) ? "?" . $urlInfo[$x]["query"] : ""; 
				$req = "GET " . $urlInfo[$x]["path"] . "$query HTTP/1.1\r\nHost: " . 
					$urlInfo[$x]["host"] . "\r\nUser-Agent: ".$user_agent."\r\nConnection: close\r\n";

				if (array_key_exists("etag", $GLOBALS['casts'][$x])) {
					$req.="If-None-Match: ".$GLOBALS['casts'][$x]['etag']."\r\n";
				}

				$req.="\r\n";

				fputs($sockets[$x], $req); 
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
						$retData[$x] .= fgets($sockets[$x],512); 
					} else { 
						$retData[$x] = fgets($sockets[$x],512); 
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
		usleep(1);
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
			$cache = json_decode($row['XML']);
			array_push($xml, $cache);
			$cast = array("id" => $row['CastID'], "url" => $row['URL']);
			if (isset($cache->etag)) {
				$cast["etag"] = $cache->etag;
			}
			array_push($GLOBALS['casts'], $cast);
		}
		$sth = $dbh->query("SELECT GUID FROM {$db_prefix}episode");
		$GLOBALS['guids'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

		$dbh->exec("UPDATE {$db_prefix}cast SET crawlts=".time());
		
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

		$GLOBALS['cast_data'] = array();

		$dbh->beginTransaction();

		$sth = $dbh->prepare("UPDATE {$db_prefix}cast SET xml=? WHERE url=?");
		foreach ($feeds as $feed) {
			echo "#".($i + 1)." ".$urls[$i]."\n";
			if ($feed != null) {
				$header = array();

				foreach (explode("\r\n", substr($feed, 0, strpos($feed, "\r\n\r\n"))) as $line) {
					$line = explode(":", $line);
					if (sizeof($line) > 1) {
						$header[$line[0]] = trim($line[1]);
					}
					else {
						$status = explode(" ", $line[0])[1];
					}
				}

				if ($status == 200) {
					$data = substr($feed, strpos($feed, "\r\n\r\n") + 4);

					$cache = array();
					if (array_key_exists("ETag", $header)) {
						$cache["etag"] = $header["ETag"];
					}
					$hash = md5($data);
					$cache["hash"] = $hash;

					if (strcmp($xml[$i]->hash, $hash) != 0) {
						$sth->execute(array(json_encode($cache), $urls[$i]));
						$t = microtime(true);
						crawl($urls[$i], $data);
						$GLOBALS['crawl_time'] += microtime(true) - $t;
					}
				}
			}
			else {
				echo "Feed failed downloading\n";
			}			
			$i++;
		}

		$push_this = $GLOBALS['push_this'];

		$t = microtime(true);
		$n = 250;
		for ($i = 0; $i < sizeof($push_this); $i += $n * 4) {
			$vals = array_slice($push_this, $i, $n * 4);
			$sth = $dbh->prepare(generateQuery(sizeof($vals) / 4));
			$sth->execute($vals);
		}
		$GLOBALS['insert_time'] = microtime(true) - $t;

		echo (sizeof($push_this) / 4)." rows inserted\n";

		$sth = $dbh->prepare("UPDATE {$db_prefix}cast SET content=? WHERE castid=?");
		foreach ($GLOBALS['cast_data'] as $id => $data) {
			$sth->execute(array($data, $id));
		}

		$dbh->commit();

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
			$feeds = multiHTTP(array_slice($urls, $j, 16));
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
	$castid = null; 
	$xml = null;

	try{
		if ($data == null) {
			$xml = simplexml_load_file($casturl, 'SimpleXMLElement', LIBXML_NOCDATA);
		}
		else {
			$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		}
	} catch (Exception $e) {}

	if ($xml) {
		$time = time();

		$t = microtime(true);

		$cast = json_decode(json_encode($xml->channel));
		$episodes = $cast->item;
		unset($cast->item);

		if (sizeof($episodes) === 1) {
			$temp = $episodes;
			$episodes = array();
			array_push($episodes, $temp);
		}

		foreach ($xml->channel->getDocNamespaces() as $ns => $nsurl) {
			foreach ($xml->channel->children($nsurl) as $child) {
				if (sizeof($child->attributes()) > 0) {
					$cast->{$ns.":".$child->getName()} = new stdClass();
					$val = (string)$child;
					if ($val !== '') {
						$cast->{$ns.":".$child->getName()}->_ = $val;
					}
					foreach ($child->attributes() as $k => $v) {
						$cast->{$ns.":".$child->getName()}->$k = (string)$v;
					}
				}
				else {
					$cast->{$ns.":".$child->getName()} = (string)$child;
				}	
			}

			for ($i = 0; $i < sizeof($episodes); $i++) {
				foreach ($xml->channel->item[$i]->children($nsurl) as $child) {
					if (sizeof($child->attributes()) > 0) {
						$episodes[$i]->{$ns.":".$child->getName()} = new stdClass();
						$val = (string)$child;
						if ($val !== '') {
							$episodes[$i]->{$ns.":".$child->getName()}->_ = $val;
						}
						foreach ($child->attributes() as $k => $v) {
							$episodes[$i]->{$ns.":".$child->getName()}->$k = (string)$v;
						}
					}
					else {
						$episodes[$i]->{$ns.":".$child->getName()} = (string)$child;
					}	
				}

				$i++;
			}
		}

		for ($i = 0; $i < sizeof($episodes); $i++) {
			foreach ($xml->channel->item[$i]->children() as $child) {
				if (sizeof($child->attributes()) > 0) {
					$episodes[$i]->{$child->getName()} = new stdClass();
					$val = (string)$child;
					if ($val !== '') {
						$episodes[$i]->{$child->getName()}->_ = $val;
					}
					foreach ($child->attributes() as $k => $v) {
						$episodes[$i]->{$child->getName()}->$k = (string)$v;
					}
				}
			}
		}

		$GLOBALS['parse_time'] += microtime(true) - $t;

		if ($data !== null) {
			$castid = where($GLOBALS['casts'], "url", $casturl)['id'];
			$GLOBALS['cast_data'][$castid] = json_encode($cast);
			//$sth = $dbh->prepare("UPDATE {$db_prefix}cast SET content=? WHERE castid=$castid");
			//$sth->execute(array(json_encode($cast)));
		}
		else {
			$sth = $dbh->query("SELECT CastID FROM {$db_prefix}cast WHERE url='$casturl'");
			$c = $sth->fetch(PDO::FETCH_ASSOC);

			if ($c) {
				$castid = $c['id'];
				$dbh->exec("UPDATE {$db_prefix}cast SET crawlts=$time WHERE castid=$castid");
			}
			else {
				$sth = $dbh->prepare("INSERT INTO {$db_prefix}cast (url, content, crawlts) VALUES(?,?,?)");
				$sth->execute(array($casturl, json_encode($cast), $time));
				$castid = $dbh->lastInsertId();
			}
		}

		if ($data !== null) {
			$guids = $GLOBALS['guids'];
		}
		else {
			$sth = $dbh->query("SELECT GUID FROM {$db_prefix}episode WHERE castid=$castid");
			$guids = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
			$sth = $dbh->prepare("INSERT INTO {$db_prefix}episode (castid, content, guid, crawlts) VALUES(?,?,?,?)");
		}

		foreach ($episodes as $episode) {
			$guid = null;
			
			if (isset($episode->guid->_)){
				$guid = $episode->guid->_;
			} else if (isset($episode->guid) && !($episode->guid instanceof stdClass)){
				$guid = $episode->guid;
			} else if (isset($episode->title)){
				$guid = $episode->title;
			}
			
			if($guid != null && !in_array($guid, $guids)) {
				if ($data !== null) {
					array_push($GLOBALS['push_this'], $castid);
					array_push($GLOBALS['push_this'], json_encode($episode));
					array_push($GLOBALS['push_this'], $guid);
					array_push($GLOBALS['push_this'], $time);
				}
				else {
					$sth->execute(array($castid, json_encode($episode), $guid, $time));					
				}				
			}
		}
	}

	return $castid;
}
?>
