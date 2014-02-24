<?php
function crawl($feedurl) {
	$dbh = $GLOBALS['dbh'];
	$time = time();
	$xml = simplexml_load_file($feedurl);

	$sth = $dbh->query("SELECT * FROM feed WHERE url='$feedurl'");
	if ($result = $sth->fetch(PDO::FETCH_ASSOC)) {
		$feedid = $result['FeedID'];
		// Update TS here
	}
	else {
		$dbh->exec("INSERT INTO feed (url, crawlts) VALUES('$feedurl', $time)");
		$feedid = $dbh->lastInsertId();
	}

	$sth = $dbh->query("SELECT * FROM feed WHERE feedid=$feedid");
	if ($sth && $sth->rowCount() < 1) {
		push_line($feedid, "channel/title", null, (string)$xml->channel->title);
		push_line($feedid, "channel/description", null, (string)$xml->channel->description);
		push_line($feedid, "channel/image/title", null, (string)$xml->channel->image->title);
		push_line($feedid, "channel/image/url", null, (string)$xml->channel->image->url);
		push_line($feedid, "channel/image/width", null, (string)$xml->channel->image->width);
		push_line($feedid, "channel/image/height", null, (string)$xml->channel->image->height);
		push_line($feedid, "channel/link", null, (string)$xml->channel->link);
		push_line($feedid, "channel/language", null, (string)$xml->channel->language);
		push_line($feedid, "channel/copyright", null, (string)$xml->channel->copyright);
	}

	foreach($xml->channel->item as $item) {
		$sth = $dbh->query("SELECT * FROM feedcontent WHERE location='channel/item/guid' AND feedid=$feedid");
		if ($sth && $sth->rowCount() > 0) {
			// Existing item
		}
		else {
			// Need new ItemID
			$dbh->exec("INSERT INTO itemid () VALUES()");
			$itemid = $dbh->lastInsertId();

			push_line($feedid, "channel/item/title", $itemid, (string)$item->title);
		}
	}
}

function push_line($feedid, $location, $itemid, $content) {

}
?>