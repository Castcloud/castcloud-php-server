<?php
/**
 * @SWG\Model(id="cast",required="id, name, url, feed")
 */
class cast
{	
    /**
     * @SWG\Property(name="id",type="integer",format="int64",description="The casts individual ID")
     */
    public $id;
	
    /**
     * @SWG\Property(name="name",type="string",description="The casts display name")
     */
    public $name;
	
    /**
     * @SWG\Property(name="url",type="string",description="The casts url")
     */
    public $url;

    /**
     * @SWG\Property(name="feed",type="array",description="All feed related data from the feed")
     */
    public $feed;
}
