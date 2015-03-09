<?php
class Episode
{
    function __construct($id, $castid, $lastevent, $feed) {
        $this->id = $id;
        $this->castid = $castid;
        $this->lastevent = $lastevent;
        $this->feed = $feed;
    }

    public $id;

    public $castid;

    public $lastevent;

    public $feed;

}
