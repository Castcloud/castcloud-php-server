<?php
include 'models/episode.php';
/**
 * @SWG\Model(id="newepisodesresult",required="timestamp, episodes")
 */
class newepisodesresult
{
	function __construct($timestamp, $episodes) {
		$this->timestamp = $timestamp;
		$this->episodes = array();
		foreach ($episodes["episodes"] as $episode) {
			$id = $episode["castcloud"]["id"];
			$castid = $episode["castcloud"]["castid"];
			$lastevent = $episode["castcloud"]["lastevent"];
			unset($episode["castcloud"]);
			array_push($this->episodes, new episode($id, $castid, $lastevent, $episode));
		}
	}

    /**
     * @SWG\Property(name="timestamp",type="integer",format="int64",description="Timestamp for the request")
     */
    public $timestamp;

    /**
     * @SWG\Property(name="episodes",type="array", items="$ref:episode", description="Array of all the episodes")
     */
    public $episodes;

}
