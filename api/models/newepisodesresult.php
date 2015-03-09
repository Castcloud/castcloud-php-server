<?php
//include 'models/episode.php';
class newepisodesresult
{
	function __construct($episodes) {
		$this->timestamp = time();
		$this->episodes = $episodes;
	}

    public $timestamp;

    public $episodes;
}
