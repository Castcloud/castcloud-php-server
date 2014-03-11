<?php
//include 'models/episode.php';
/**
 * @SWG\Model(id="newepisodesresult",required="timestamp, episodes")
 */
class newepisodesresult
{
	function __construct($episodes) {
		$this->timestamp = time();
		$this->episodes = $episodes;
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
