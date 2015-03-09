<?php
class eventsresult
{
	function __construct($events) {	
		$this->timestamp = time();
		$this->events = $events;
	}
	
    public $timestamp;

    public $events;
}
