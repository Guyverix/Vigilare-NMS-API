<?php
declare(strict_types=1);

namespace App\Application\Actions\Infrastructure;

use Psr\Http\Message\ResponseInterface as Response;

class ViewInfrastructureAction extends InfrastructureAction {
    /**
     * {@inheritdoc}
     */
  protected function action(): Response  {

    $action=$this->resolveArg("action");
    $data = $this->getFormData();

    if ($action == "findChildren" ) {
      $parent = $data['parent'];
      $infrastructure = $this->infrastructureRepository->findChildren($parent);
      $this->logger->info("Infrastructures retrieved children of parent " . $parent);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "findChildrenOfParent") {
      $category_id = $data['category_id'];
      $this->logger->info("Retrieve children of category_id " . $category_id);
      $infrastructure = $this->infrastructureRepository->findchildrenOfParent($category_id);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "findOrphans") {
      $infrastructure = $this->infrastructureRepository->findOrphans();
      $this->logger->info("Retrieve any orphan hosts found");
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "newHost") {
      $hostArray=array();
      $hostArray['hostname'] = $data['hostname'];
      $hostArray['category_id'] = $data['category_id'];
      $infrastructure = $this->infrastructureRepository->newHost($hostArray);
      $this->logger->info("Add new host " . $hostArray['hostname'] . " in category_id " . $hostArray['category_id']);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "updateHost") {
      $hostArray=array();
      $hostArray['hostname'] = $data['hostname'];
      $hostArray['category_id'] = $data['category_id'];
      $hostArray['product_id'] = $data['product_id'];
      $infrastructure = $this->infrastructureRepository->updateHost( $hostArray);
      $this->logger->info("Update location for host " . $hostArray['hostnanme'] . " to " . $hostArray['category_id'] . " product_id " . $hostArray['product_id']);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "deleteHost") {
      $hostArray=array();
      $hostArray['hostname'] = $data['hostname'];
      $infrastructure = $this->infrastructureRepository->deleteHost($hostArray);
      $this->logger->info("Delete host from system " . $hostArray['hostname']);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "findCategory") {
      $infrastructure = $this->infrastructureRepository->findCategory();
      $this->logger->info("Return all categorys that are defined");
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "newCategory") {
      $categoryArray=array();
      $categoryArray['parent_id'] = $data['parent_id'];
      $categoryArray['category_name'] = $data['category_name'];
      $infrastructure = $this->infrastructureRepository->newCategory($categoryArray);
      $this->logger->info("Create new category of " . $categoryArray['category_name'] . " with parent id of " . $CategoryArray['parent_id']);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "updateCategory") {
      $categoryArray=array();
      $categoryArray['parent_id'] = $data['parent_id'];
      $categoryArray['category_name'] = $data['category_name'];
      $infrastructure = $this->infrastructureRepository->updateCategory($categoryArray);
      $this->logger->info("Change the category parent id of " . $categoryArray['category_name'] . " to parent id " . $categoryArray['parent_id']);
      return $this->respondWithData($infrastructure);
    }

    elseif ($action == "validateCategoryBeforeDelete") {
      $category_id = $data['category_id'];
      $infrastructure = $this->infrastructureRepository->validateCategoryBeforeDelete($category_id );
      $this->logger->info("Count children of category before deletions");
      return $this->respondWithData($infrastructure);
    }
    elseif ($action == "validateHostBeforeDelete") {
      $category_id = $data['category_id'];
      $infrastructure = $this->infrastructureRepository->validateHostBeforeDelete($category_id);
      $this->logger->info("Count number of affected hosts before deletions.");
      return $this->respondWithData($infrastructure);
    }
    elseif ($action == "deleteCategory") {
      $categoryArray=array();
      $categoryArray['parent_id'] = $data['parent_id'];
      $categoryArray['category_id'] = $data['category_id'];
      $infrastructure = $this->infrastructureRepository->deleteCategory( $categoryArray);
      $this->logger->info("Delete category_id " . $categoryArray['category_id'] . " from parent id " . $categoryArray['parent_id']);
      return $this->respondWithData($infrastructure);
    }

    else {
      $infrastructure = "No valid action called.  Try: findChildren findChildrenOfParent findOrphans newHost updateHost deleteHost findCategory newCategory updateCategory validateCategoryBeforeDelete validateHostBeforeDelete deleteCategory";
      $this->logger->warning("Route called with no action set in URL.");
      return $this->respondWithData($infrastructure);
    }
  }
}
