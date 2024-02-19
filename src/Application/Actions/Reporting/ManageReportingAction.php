<?php
declare(strict_types=1);

namespace App\Application\Actions\Reporting;

//use App\Application\Validation\Reporting\ReportingValidator;  // not yet
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
class ManageReportingAction extends ReportingAction {
   /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $jobType=["test", "purge", "run", "searchComplete", "viewComplete", "searchTemplate", "createReport", "findPending", "runPending"]; // sanity check that we only are doing what we expect here

    // How to check if resolveArg is even going to work
    // before calling it and kicking an exception
    if ( empty($this->args["action"]) ) {
      $action="failure";
    }
    else {
      $action=$this->resolveArg("action");
    }

    // All the different POST data we could have as an array
    $data = $this->getFormData();

    // Setup our valiation now
//    $validator = new ReportingValidator();

    switch ($action) {
    // Fail fast if we are never going to be able to do anything
    case in_array("$action", $jobType) == false:
      $x='';
      foreach ($jobType as $list) { $x = $x ." " . $list; }
      $jobTypeText="supported actions: " . $x;
      unset ($x);
      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage Reporting Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
      return $this->respondWithData($job);  // Wont get to this point due to throw but meh.
      break;
    case "searchTemplate":
      $report = $this->reportingRepository->searchTemplate();
      break;
    case "test":
      $report = ["test" => "success"];
      break;
    case "purge":
      $report = $this->reportingRepository->purgeReport($data);
      break;
    case "run":
      $report = $this->reportingRepository->runReport($data);
      break;
    case "runPending":
      $report = $this->reportingRepository->runPending($data);
      break;
    case "createReport":
      $report = $this->reportingRepository->createReport($data);
      break;
    case "findPending":
      $report = $this->reportingRepository->findPending();
      break;
    case "searchComplete":
      $report = $this->reportingRepository->searchReport();
      break;
    case "viewComplete":
      $report = $this->reportingRepository->viewReport($data);
      break;
    } // end switch
  $this->logger->info("manageReportingAction call for " . $action . " query data " . json_encode($data,1));
  return $this->respondWithData($report);
  } // end function
} // end class

