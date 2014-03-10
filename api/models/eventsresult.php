<?php
namespace Castcloud\Models;

use Swagger\Annotations as SWG;

/**
 * @SWG\Model(id="eventsresult",required="timestamp, events")
 */
class newepisodesresult
{
    /**
     * @SWG\Property(name="timestamp",type="integer",format="int64",description="Timestamp for the request")
     */
    public $timestamp;

    /**
     * @SWG\Property(name="events",type="array", items="$ref:event", description="Array of all the events")
     */
    public $events;

}
