<?php
/**
 * @SWG\Model(id="episode",required="id, castid")
 */
class Episode
{
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
     * @SWG\Property(name="feeddata",type="array",@SWG\Items("string"),description="All data available in the episodes item")
     */
    public $feeddata;

}
