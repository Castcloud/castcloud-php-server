<?php
/**
 * @SWG\Model(id="event",required="type, episodeid, positionts, clientts")
 */
class event
{
	function __construct($type, $episodeid, $positionts, $clientts, $concurrentorder, $clientname, $clientdescription) {
        $this->type = $type;
        $this->episodeid = $episodeid;
        $this->positionts = $positionts;
        $this->clientts = $clientts;
        $this->concurrentorder = $concurrentorder;
        $this->clientname = $clientname;
        $this->clientdescription = $clientdescription;
    }
	
    /**
     * @SWG\Property(name="type",type="integer",format="int64",description="The event type")
     */
    public $type;

    /**
     * @SWG\Property(name="episodeid",type="integer",format="int64",description="The id of the episode it relates to")
     */
    public $episodeid;

    /**
     * @SWG\Property(name="positionts",type="integer",format="int64",description="The position where the event occured")
     */
    public $positionts;

    /**
     * @SWG\Property(name="clientts",type="integer",format="int64",description="The clients unix timestamp of when the event occured")
     */
    public $clientts;
	
	/**
     * @SWG\Property(name="concurrentorder",type="integer",format="int64",description="If multiple events occure on the same time, This wil decribe the order of the events.")
     */
    public $concurrentorder;
	
	/**
     * @SWG\Property(name="clientname",type="string",description="Name of the client that sent the event")
     */
    public $clientname;
	
	/**
     * @SWG\Property(name="clientdescription",type="string",description="Description of the client that sent the event")
     */
    public $clientdescription;

}
