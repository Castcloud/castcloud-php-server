<?php
/**
 * @SWG\Model(id="cast",required="id, url, tags, feed")
 */
class cast
{
    function __construct() {
        $this->tags = explode(',', $this->tags);
    }
	
    /**
     * @SWG\Property(name="id",type="integer",format="int64",description="The casts individual ID")
     */
    public $id;
	
    /**
     * @SWG\Property(name="url",type="string",description="The casts url")
     */
    public $url;

    /**
     * @SWG\Property(name="tags",type="array",@SWG\Items("string"),description="All the casts tags")
     */
    public $tags;

    /**
     * @SWG\Property(name="feed",type="array",description="All feed related data from the feed")
     */
    public $feed;
}
