<?php
include 'models/event.php';
/**
 * @SWG\Model(id="eventsresult",required="timestamp, events")
 */
class eventsresult
{
	function __construct($userid, $itemid, $since) {	
		$db_prefix = $GLOBALS['db_prefix'];
		$this->timestamp = time();
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
			ORDER BY
			event.clientts DESC,
			event.concurrentorder DESC
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

		$dbh = $GLOBALS['dbh'];
		$sth = $dbh -> prepare($query);
		$sth->execute($inputs);
		if ($sth) {
			$this->events = $sth->fetchAll(PDO::FETCH_CLASS, "event");
		}
	}
	
    /**
     * @SWG\Property(name="timestamp",type="integer",format="int64",description="Timestamp for the request")
     */
    public $timestamp;

    /**
     * @SWG\Property(name="events",type="array", items="$ref:event", description="Array of all the events")
     */
    public $events;

}
