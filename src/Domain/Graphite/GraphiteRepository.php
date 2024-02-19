<?php
declare(strict_types=1);

namespace App\Domain\Graphite;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';                   // simple Database class to leverage
require __DIR__ . '/../../../app/Curl.php';                       // simple Curl class to leverage
require __DIR__ . '/../../../templates/generalMetricRender.php';  // Class that returns Graphite URLs to consume (renderGraphite)

//include_once __DIR__ . '/../../../app/config.php';   // Graphite URL and ports are defined in here

// This is used for direct filesystem stuff kinda like ls, etc
include_once __DIR__ . '/../../Infrastructure/Shared/Functions/filesystemFunctions.php';

// Does NOT follow CRUD as we can only view what graphite has existing
interface GraphiteRepository {

    public function findRegex($arr);
    public function findList($arr);
    public function findChecks($arr);
    public function findMonitored($arr);
    public function findFunction($arr);
    public function findSiblings($arr);
    public function findMap($arr);
    public function findSingleMap($arr);
    public function findAll();

//  New functions relating to templating
    public function findTemplate($arr);
    public function findMetrics($arr);
    public function findGraphs($arr);
    public function expandChecks($arr);                          // returns simple array of defined checks.    Does not state if is leaf or not
    public function expandMetricsExpanded($arr);                 // returns simple array of defined metricNames
/*
    public function findRegex($hostname ,$graphiteRegex);        // Retrieve a small list filtered against a service check
    public function findList($hostname);                         // Retrieve all monitors against a hostname
    public function findChecks($hostDaemon);                     // Retrieve all checks against a daemon
    public function findMonitored($hostname);                    // Retrieve monitored names for host
    public function findFunction($hostname, $graphiteFindList);  // Retrieve any special functions for the returns
    public function findSiblings($hostname, $graphiteFindList);  // Retrieve any special functions AND siblings that match a regex pattern
    public function findMap($hostname, $metricId);               // Query database for adding functions
    public function findSingleMap($hostname, $metricId);         // Query database for adding functions with a SINGLE metric
    public function findAll();                                   // Query base graphite for ALL metrics defined.  Slow and large!
*/

}


/*


findRegex: hostname, checkName.value
  returns only what matches checkName.value for host.  Minimal return array

findList: hostname
  returns everything that is monitored for that hostname up to 2 levels down.
  In general we are not going to allow heavily nested values.  This system
  is designed to be as simple as possible.  Due to that we are only
  really checking for PREFIX.HOST.DAEMON_NAME.CHECK.VALUE

findMonitored: hostname
  returns the daemon names that are adding input to graphite
  allows for filtering such as snmp or nrpe or shell or ???

findFunction: hostname, list of metrics to append functions to
  Iterate through everything that is found from the other function call
  and update the resulting output so that we can add things such as derivative
  and other special values from the database.  V2 should have this in a redis cache
  maybe?  Dont need to hammer the db if we get really large datasets?

findSiblings: hostname list of metrics to append functions to
  Iterate through the list and do things such as join a second metric to the first
  in a programatic way.  ineterface in and out in the same graph is a good example
  This will also need to filter out reverse matches so interface out and in do not build

findAll: no args
  Pull the graphite metrics URL and get the entire list of json metrics graphite has
  This is VERY heavy, and should be for debugging, and making new graphs only..

*/
