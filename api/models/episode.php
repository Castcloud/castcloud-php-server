<?php
/**
 * @SWG\Model(id="episode",required="id, castid")
 */
class Episode
{
    function __construct($id, $castid, $lastevent, $feed) {
        $this->id = $id;
        $this->castid = $castid;
        $this->lastevent = $lastevent;
        $this->feed = $feed;
    }

    /**
     * @SWG\Property(name="id",type="integer",format="int64",description="Unique identifier for the episode")
     */
    public $id;

    /**
     * @SWG\Property(name="castid",type="integer",format="int64",description="Unique identifier for the cast related to the episode")
     */
    public $castid;

    /**
     * @SWG\Property(name="lastevent",type="event",description="The episodes last event")
     */
    public $lastevent;

    /**
     * @SWG\Property(name="feed",type="array",description="All data available in the episodes item")
     */
    public $feed;

}
