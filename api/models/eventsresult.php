<?php
include 'models/event.php';
/**
 * @SWG\Model(id="eventsresult",required="timestamp, events")
 */
class eventsresult
{
	function __construct($events) {	
		$this->timestamp = time();
		$this->events = $events;
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
