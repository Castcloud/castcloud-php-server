<?php
function crawl($feedurl) {
	$dbh = $GLOBALS['dbh'];
	$time = time();
	$xml = simplexml_load_file($feedurl);

	$sth = $dbh->query("SELECT * FROM feed WHERE url='$feedurl'");
	if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
		$feedid = $result['FeedID'];
		
		$dbh->exec("UPDATE feed SET crawlts=$time");
	}
	else {
		$dbh->exec("INSERT INTO feed (url, crawlts) VALUES('$feedurl', $time)");
		$feedid = $dbh->lastInsertId();
	}

	$sth = $dbh->query("SELECT * FROM feedcontent WHERE feedid=$feedid");
	if ($sth && $sth->rowCount() < 1) {
		push_line($feedid, "channel/title", null, (string)$xml->channel->title, $time);
		push_line($feedid, "channel/description", null, (string)$xml->channel->description, $time);
		push_line($feedid, "channel/image/title", null, (string)$xml->channel->image->title, $time);
		push_line($feedid, "channel/image/url", null, (string)$xml->channel->image->url, $time);
		push_line($feedid, "channel/image/width", null, (string)$xml->channel->image->width, $time);
		push_line($feedid, "channel/image/height", null, (string)$xml->channel->image->height, $time);
		push_line($feedid, "channel/link", null, (string)$xml->channel->link, $time);
		push_line($feedid, "channel/language", null, (string)$xml->channel->language, $time);
		push_line($feedid, "channel/copyright", null, (string)$xml->channel->copyright, $time);
	}

	foreach($xml->channel->item as $item) {
		$sth = $dbh->query("SELECT * FROM feedcontent WHERE location='channel/item/guid' AND content='$item->guid' AND feedid=$feedid");
		if ($sth && $sth->rowCount() > 0) {
			// Existing item
		}
		else {
			$dbh->exec("INSERT INTO itemid () VALUES()");
			$itemid = $dbh->lastInsertId();

			push_line($feedid, "channel/item/title", $itemid, (string)$item->title, $time);
			push_line($feedid, "channel/item/description", $itemid, (string)$item->description, $time);
			push_line($feedid, "channel/item/pubdate", $itemid, (string)$item->pubdate, $time);
			push_line($feedid, "channel/item/guid", $itemid, (string)$item->guid, $time);
		}
	}

	return $feedid;
}

function push_line($feedid, $location, $itemid, $content, $time) {
	$sth = $GLOBALS['dbh']->prepare("INSERT INTO feedcontent (feedid, location, itemid, content, crawlts) VALUES($feedid, '$location', :itemid, '$content', $time)");
	$sth->bindParam(':itemid', $itemid, PDO::PARAM_INT);
	$sth->execute();
}

function crawler_get($feedid, $location) {
	$sth = $GLOBALS['dbh']->query("SELECT * FROM feedcontent WHERE feedid=$feedid AND location='$location'");
	if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
		return $result['Content'];
	}
	return "";
}

function crawler_get_all($feedid, $location) {
	$sth = $GLOBALS['dbh']->query("SELECT * FROM feedcontent WHERE feedid=$feedid AND location='$location'");
	if ($result = $sth->fetchAll()) {
		$list = array();
		foreach ($result as $row) {
			array_push($list, $row['Content']);
		}
		return $list;
	}
	return null;
}
?>