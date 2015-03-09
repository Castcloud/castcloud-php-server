<?php
class label
{
	function __construct()
	{
		$this->expanded = (bool)$this->expanded;
        if (strpos($this->name,"label/") === 0) {
            $this->name = substr($this->name, 6);
            $this->root = false;
        }
        else {
            $this->root = true;
        }
	}
	
    public $id;
	
    public $name;
	
    public $content;

    public $expanded;

    public $root;
}
