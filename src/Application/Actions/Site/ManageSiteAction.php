<?php
declare(strict_types=1);

namespace App\Application\Actions\Site;

use Slim\Exception\HttpBadRequestException;
use App\Application\Validation\Site\SiteValidator;
use App\Application\Actions\Site\SiteAction;
use App\Domain\Site\SiteRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageSiteAction extends SiteAction {
  protected function action(): Response {

    // Define the allowed jobs this class can do
    $jobType = ['getAllHostnames', 'getHostnameFromGroupName', 'getGroupNamesFromHostname', 'addGroupName', 'deleteGroupName', 'addHostname', 'deleteHostname', 'cleanHostname'];

    /* BOILERPLATE
       This should be a generic way for each route to vet the supported arguments
       everything SHOULD be done this way if at all possible
    */
    // check if resolveArg is even going to work before calling it and kicking an exception
    if ( empty($this->args["action"]) ) { $action="failure";} else { $action=$this->resolveArg("action"); }

    // Fail fast if we are never going to be able to do anything
    if ( ! in_array("$action", $jobType) ) {
      $x='';
      foreach ($jobType as $list) {
        $x = $x ." " . $list;
      }
      $jobTypeText="supported actions: " . $x;
      unset ($x);

      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("ManageSite Action no valid action type defined for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }
    // pull in any form data we may have
    $data = $this->getFormData();

    $validator = new SiteValidator();

    /* END BOILERPLATE */

    switch ($action) {
      case 'getAllHostnames':
        $queryResult = $this->siteRepository->getAllHostnames();
        break;
      case 'getHostnameFromGroupName':
        $validator->group($data);
        $queryResult = $this->siteRepository->getHostnameFromGroupName($data);
        break;
      case 'getGroupNamesFromHostname':
        $validator->id($data);
        $queryResult = $this->siteRepository->getGroupNamesfromHostname($data);
        break;
      case 'addGroupName':
        $validator->group($data);
        $queryResult = $this->siteRepository->addGroupName($data);
        break;
      case 'deleteGroupName':
        $validator->group($data);
        $queryResult = $this->siteRepository->deleteGroupName($data);
        break;
      case 'addHostname':
        $validator->both($data);
        $queryResult = $this->siteRepository->addHostname($data);
        break;
      case 'deleteHostname':
        $validator->both($data);
        $queryResult = $this->siteRepository->deleteHostname($data);
        break;
      case 'cleanHostname':
        $validator->id($data);
        $queryResult = $this->siteRepository->cleanHostname($data);
        break;
      case 'ping':
        $queryResult = ["pong"];
        break;
      default:
        $this->logger->error("Route called with no valid action set in URL.");
        throw new HttpBadRequestException($this->request, "Route called with no valid action set in URL.");
        break;
    }
    // Assuming no errors, log the query and return the data
    $this->logger->info("Completed " . $action, $data);
    return $this->respondWithData($queryResult);
  }
}

