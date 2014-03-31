<?php
/**
 * @SWG\Model(id="opml",required="id, url, tags, feed")
 */
class opml
{
    /**
     * @SWG\Property(name="title",type="string",description="The casts individual ID")
     */
    public $title;
	
    /**
     * @SWG\Property(name="dateCreated",type="string",description="The casts url")
     */
    public $dateCreated;

    /**
     * @SWG\Property(name="tags",type="string",description="All the casts tags")
     */
    public $ownerName;

    /**
     * @SWG\Property(name="ownerEmail",type="string",description="All feed related data from the feed")
     */
    public $ownerEmail;
	
	/**
     * @SWG\Property(name="tags",type="array",items="$ref:opml_tag",description="All feed related data from the feed")
     */
    public $tags;
	
	/**
     * @SWG\Property(name="untagged",type="array",items="$ref:opml_cast",description="The casts url")
     */
    public $untagged;
}

/**
 * @SWG\Model(id="opml_tag",required="id, url, tags, feed")
 */
class opml_tag
{	
    /**
     * @SWG\Property(name="title",type="string",description="The casts individual ID")
     */
    public $title;
	
    /**
     * @SWG\Property(name="casts",type="array",items="$ref:opml_cast",description="The casts url")
     */
    public $casts;
}

/**
 * @SWG\Model(id="opml_cast",required="id, url, tags, feed")
 */
class opml_cast
{	
    /**
     * @SWG\Property(name="name",type="integer",format="int64",description="The casts individual ID")
     */
    public $title;
	
    /**
     * @SWG\Property(name="casts",type="string",description="The casts url")
     */
    public $url;
     
	/**
     * @SWG\Property(name="description",type="string",description="The casts url")
     */
    public $description;
}
