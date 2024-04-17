<?php
declare(strict_types=1);

namespace App\Application\Actions\Metrics;

use App\Application\Validation\Metrics\MetricsValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ManageMetricsAction extends MetricsAction {
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $jobType=["validate", "clean", "add", "test", "queue"]; // sanity check that we only are doing what we expect here

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
    // $validator = new MetricsValidator();

    switch ($action) {
    // Fail fast if we are never going to be able to do anything
    case in_array("$action", $jobType) == false:
      $x='';
      foreach ($jobType as $list) { $x = $x ." " . $list; }
      $jobTypeText="supported actions: " . $x;
      unset ($x);
      $job = "No valid action type set.  Try " . $jobTypeText;
      $this->logger->error("Manage Metrics Action no valid action type given for requested action " . $action );
      throw new HttpBadRequestException($this->request, $job);
      return $this->respondWithData($job);
      break;
    case "add":
      $metrics = $this->metricsRepository->createMetrics($data);
      $this->logger->info("Add metric values for storage " . json_encode($data, 1 ));
      break;
    case "queue":
      $metrics = $this->metricsRepository->queueMetrics($data);
      $this->logger->info("Queue metric values for storage " . json_encode($data, 1 ));
      break;
    case "validate":
      $validator->properties($data);
      $metrics = $this->metricsRepository->validateMetrics($data);
      $this->logger->info("Validate raw metrics " . json_encode($data, 1));
      break;
    case "clean":
      // $validator->performance($data);
      $metrics = $this->metricsRepository->cleanMetrics($data);
      $this->logger->info("Clean raw metrics " . json_encode($data, 1));
      break;
    case "test":
      /* make damn sure we have what we need here */
      $metrics = ["test" => "success"];
      break;
    } // end switch
  $this->logger->info("manageMetricsAction call for " . $action . " query data " . json_encode($data,1));
  return $this->respondWithData($metrics);
  } // end function
} // end class

