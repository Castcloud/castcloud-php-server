<?php
class setting
{
    function __construct() {
        $this->clientspecific = (bool)$this->clientspecific; // ;)
    }
	
    public $settingid;
	
    public $setting;

    public $value;

    public $clientspecific;
}
