<?php
/**
 * @SWG\Model(id="cast",required="id, name, url, feed")
 */
class label
{
	function __construct()
	{
		$this->expanded = (bool)$this->expanded;
	}
	
    /**
     * @SWG\Property(name="id",type="integer",format="int64",description="The labels individual ID")
     */
    public $id;
	
    /**
     * @SWG\Property(name="name",type="string",description="The label structure. root is the root directory. All labels are formated label/labelname where labelname is the name of the label")
     */
    public $name;
	
    /**
     * @SWG\Property(name="url",type="string",description="CSV containging the label order. Labels are formated label/labelid where labelid is the labels uniqe id. Casts are formated cast/castid where cast is the casts unique id Only root can contain labels.")
     */
    public $content;

    /**
     * @SWG\Property(name="expanded",type="boolean",description="If the label is open or closed")
     */
    public $expanded;
}
