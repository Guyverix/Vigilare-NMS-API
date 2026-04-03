<?php
declare(strict_types=1);
namespace App\Domain\Metrics;

// future support stubs
// require_once __DIR__ . '/../../../app/influxdb.php';    // already defined at a basic level in the templates dir..
// require_once __DIR__ . '/../../../templates/influxdb.php';
// require_once __DIR__ . '/../../../app/queue.php';

/*
  supporting RRD, and Graphite
  This is going to be calling functions, not classes.

  Keep in mind storing metrics this way is EXPENSIVE!
  Best solution would be to offload to a queue.  But that will
  be V2 or perhaps V3 depending.

  A unified solution for all pollers would be best, but I am not even
  at design phase for that yet.
*/
require_once __DIR__ . '/../../../templates/generalMetricSaver.php';

/*
  Saving metrics to database is valid but not common
*/
require __DIR__ . '/../../../app/Database.php';

/*
  We are stil going to leverage the persistence directory
  so we can get the database part consistent.  Might as well
  add our function calls in there too even if they are not for
  the database itself specifically.

  Likely this will eventually contain the queue portion as well (maybe)
*/

interface MetricsRepository {
    // Create Metrics stuff
    public function add($arr): array;       // Insert a new metric value
    public function queue($arr): array;     // Drop into a queue of some kind and return

    // Retrieve Information of some kind
    public function validate($arr): array;  // Confirms input adheres to standards for Inserts
    public function clean($arr): array;     // Clean input data and drop invalid data
    public function get($arr):array;        // Generic catchall that should be under metric renddering
}
