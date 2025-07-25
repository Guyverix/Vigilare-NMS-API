<?php
declare(strict_types=1);

namespace App\Application\Actions\RenderGraph;

use Slim\Exception\HttpBadRequestException;
use App\Application\Actions\RenderGraph\RenderGraphAction;
use App\Domain\RenderGraph\RenderGraphRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ManageRenderGraphAction extends RenderGraphAction {
  protected function action(): Response {
    $jobType=["render", "delete", "link", "debug", "metrics", "findRrd", "findGraphite", "findRrdTemplates"];  // Actions used for render graph

    // How to check if resolveArg is even going to work
    // before calling it and kicking an exception
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
      $this->logger->error("Manage RenderGraph Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
    }


    // This can be empty, so dont get bit here if there are no posted vars
    // This will always be an array
    $data = $this->getFormData();
    $data['action'] = $action;
    // return $this->respondWithData($data);
    if("$action" == "render") {
      $this->logger->debug("ManageRenderGraphAction.php action render " . json_encode($data,1));
      $FindRenderGraph=$this->renderGraphRepository->createGraph($data);  // this is an array returns image link as array
    }
    elseif ($action == "findRrd") {
      $FindRenderGraph=$this->renderGraphRepository->findRrdDatabases($data); // this is an array returns filesystem list of rrd
    }
    elseif ($action == "findRrdTemplates") {
      $FindRenderGraph=$this->renderGraphRepository->findRrdTemplates($data); // this is an array returns filesystem list of rrd
    }
    elseif ($action == "findGraphite") {
      $FindRenderGraph=$this->renderGraphRepository->findGraphiteLinks($data); // this is an array returns list of graphite graphs
    }
    elseif ($action == "delete") {
      $FindRenderGraph=$this->renderGraphRepository->deleteGraph($data);  // this is an array returns 200 no data as array
    }
    elseif ($action == "link") {
      $FindRenderGraph=$this->renderGraphRepository->linkGraph($data);  // this is an array returns filesystem list of rrds
    }
    elseif ($action == "metrics") {
      $FindRenderGraph=$this->renderGraphRepository->graphMetricSetup($data);  // this is an array returns array
    }
    else { // debug is going to be the default
      $FindRenderGraph=$data;  // this is an array returns same array
    }
    $this->logger->info("Find renderGraph values for " . $action . " with values " . json_encode($data, 1));
    return $this->respondWithData($FindRenderGraph);
  } // end function
}  // end class
