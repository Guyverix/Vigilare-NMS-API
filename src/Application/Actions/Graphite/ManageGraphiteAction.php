<?php

declare(strict_types=1);

namespace App\Application\Actions\Graphite;

use App\Application\Actions\Graphite\GraphiteAction;
use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ResponseInterface as Response;

/*
  This is going to be the default route using POST
  so we can pass arguments to the calls.  This is the first
  step for leveraging graphite templates which
  will live in /templates/grapite/template_<NAME>.php

  <NAME> will equate to the checkName that the metric was saved
  under, and will contain all different returns possible for the
  metric that are predefined.

  This means a given metric can have N+1 graphs returned
  for the given checkName data.

  Adhoc manipulation will only be allowed for timeframe, and size changes.
  Other than that, the templates control details for how to manipulate
  the data.
*/

class ManageGraphiteAction extends GraphiteAction {
  protected function action(): Response {
    $jobType=["source", "check", "single", "createUrl", "findAll", "findList", "findMonitored", "findChecks", "findFunction", "findSiblings", "findRegex", "findMap", "findSingleMap", "test", "findMetrics", "findGraphs"];  // Available options for $data['task']

    $data = $this->getFormData();

    if (! isset($data['prefix']))    { $data['prefix'] = 'nms'; }        // graphite prefix
    if (! isset($data['from']))      { $data['from'] = "-1d"; }          // default from is -24 hours
    if (! isset($data['to']))        { $data['to'] = "-1m"; }            // default is now - 1 minute
    if (! isset($data['width']))     { $data['width'] = "586"; }         // Default render width
    if (! isset($data['height']))    { $data['height'] = "308"; }        // Default render height
    if (! isset($data['return']))    { $data['return'] = "json"; }       // Default return style
    if (! isset($data['checkName'])) { $data['checkName'] = 'none'; }    // Filter for checkName in the Graphite URLs returned
    if (! isset($data['task']))      { $data['task'] = 'none'; }         // Define task we need to do from POST
    if (! isset($data['template']))  { $data['template'] = 'none'; }     // Give the ability to use a specific template rather than parsing
    if (! isset($data['hostname']))  { $data['hostname'] = 'none'; }     // keep in mind IP and hostname are valid for Device, but NOT for Graphite. Graphite is going to use hostname from the database
    if (! isset($data['checkType'])) { $data['checkType'] = 'none'; }    // Define the poller name so we can find the checkNames below it

    $data['hostname']= preg_replace('/\./','_', $data['hostname']);      // Graphite hostname/IP ALWAYS has period changed to underscore!
    $status=200;

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array($data['task'], $jobType)) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);
      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage Graphite Action no valid action type given for requested action " . $data['task'] );
      throw new HttpBadRequestException($this->request, $job);
    }

    /*
      Assume minimal information was given.
      hostname is manditory, but other than that rely on what is being asked for as far as the task.
      Leverage the other functions to get to the desired state for your task
    */


    switch( $data['task'] ) {
      case "createUrl":
        $graphiteReturnData=$this->graphiteRepository->createUrl($data);      // End point which returns viewable URLs with all functions applied
        break;
      case "findGraphs":
        $graphiteReturnData=$this->graphiteRepository->findGraphs($data);     // dupe of createurl() for testing
        break;
      case "findAll":
        $graphiteReturnData=$this->graphiteRepository->findAll();             // SLOW.  Returns everything from Graphite.  This should be used for debugging, not normal use
        break;
      case "findList":
        $graphiteReturnData=$this->graphiteRepository->findList($data);       // Returns list of all metricNames, NOT poller, or checkName ( assuming conventions are followed )
        break;
      case "findMonitored":
        $graphiteReturnData=$this->graphiteRepository->findMonitored($data);  // Returns list of prefix.hostname.poller in id, and poller name in text
        break;
      case "findChecks":
        $graphiteReturnData=$this->graphiteRepository->findChecks($data);     // Returns checkName in text, and path in id
        break;
      case "findMetrics":
        $graphiteReturnData=$this->graphiteRepository->findMetrics($data);    // Returns metricNames associated with checkName
        break;
      case "source":
        $graphiteReturnData=$this->graphiteRepository->source($data);         // to be deprecated
        break;
      case "check":
        $graphiteReturnData=$this->graphiteRepository->check($data);          // to be deprecated
        break;
      case "single":
        $graphiteReturnData=$this->graphiteRepository->single($data);         // to be deprecated
        break;
      case "findFunction":
        $graphiteReturnData=$this->graphiteRepository->findFunction($data);   // to be deprecated
        break;
      case "findSiblings":
        $graphiteReturnData=$this->graphiteRepository->findSibilings($data);  // to be deprecated
        break;
      case "findRegex":
        $graphiteReturnData=$this->graphiteRepository->findRegex($data);      // to be deprecated
        break;
      case "findMap":
        $graphiteReturnData=$this->graphiteRepository->findMap($data);        // to be deprecated
        break;
      case "findSingleMap":
        $graphiteReturnData=$this->graphiteRepository->findSingleMap($data);  // to be deprecated
        break;
      case "test":
        $data['test'] = 'Test routing result is successful';
        $graphiteReturnData=$data;
        $status=202;
        break;
      default :
        $graphiteReturnData=['Unexpected failure in case match'];
        $status=418;
        break;
    }

    $this->logger->info("Find Graphite values for action " . $data['task'] . " with values " . json_encode($data, 1));

    // New type of response.  You must always define $status for this to work correctly!
    return $this->respondWithData($graphiteReturnData, $status);  // old style hard to set new HTML response codes
  } // end function
}  // end class
