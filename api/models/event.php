<?php
class event
{
	function __construct($type = null, $episodeid = null, $positionts = null, $clientts = null, $concurrentorder = null, $clientname = null, $clientdescription = null) {
		if (!isset($this->type)){
			$this->type = $type;
			$this->episodeid = $episodeid;
			$this->positionts = $positionts;
			$this->clientts = $clientts;
			$this->concurrentorder = $concurrentorder;
			$this->clientname = $clientname;
			$this->clientdescription = $clientdescription;
		}
    }
	
    public $type;

    public $episodeid;

    public $positionts;

    public $clientts;
	
    public $concurrentorder;
	
    public $clientname;
	
    public $clientdescription;
}
