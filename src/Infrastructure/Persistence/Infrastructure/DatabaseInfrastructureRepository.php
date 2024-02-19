<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Infrastructure;

use App\Domain\Infrastructure\Infrastructure;
use App\Domain\Infrastructure\InfrastructureNotFoundException;
use App\Domain\Infrastructure\InfrastructureRepository;

// this require needs to be moved to Domain/InfrastureRepository
require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseInfrastructureRepository implements InfrastructureRepository {
  public $db;

  public function __construct() {
    $this->db = new Database();
  }

  /* this is the list of categories */
  public function findChildren(string $parent): array {
    $this->db->prepare("SELECT category_name, category_id, category_link FROM infrastructure WHERE parent_id = :parent");
    $this->db->bind('parent', $parent);
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* this is the list of hosts that map to the above categories */
  public function findChildrenOfParent(string $category_id): array {
    $this->db->prepare("SELECT product_name, product_id, product_link, category_id FROM infrastructureProducts WHERE category_id = :category_id");
    $this->db->bind('category_id', $category_id);
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* this is the list of hosts that DO NOT map to the above categories, or are set as "unknown" */
  public function findOrphans(): array {
    $this->db->prepare("SELECT product_name, product_id, product_link FROM infrastructureProducts WHERE category_id = 20 OR category_id = NULL");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Return names and ID of categories */
  public function findCategory(): array {
    $this->db->prepare("SELECT parent_id, category_name FROM infrastructure");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Add new host with category_id */
  public function newHost($detal): array {
    $this->db->prepare("");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Change host to new category_id */
  public function updateHost($detail): array {
    $this->db->prepare("");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Delete host */
  public function deleteHost($detail): array {
    $this->db->prepare("");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Create new category.  Set parent_id  and name */
  public function newCategory($detail): array {
    $this->db->prepare("");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* change category.  Set different parent_id */
  public function updateCategory($detail): array {
    $this->db->prepare("");
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Verify that no hosts are attached to a catgory that is being deleted */
  public function validateHostBeforeDelete($category_id): array {
    $this->db->prepare("SELECT COUNT(*) as count FROM infrastructureProducts WHERE category_id = :category_id");
    $this->db->bind('category_id', $category_id);
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Verify that no categories are children OR parents of category to delete */
  public function validateCategoryBeforeDelete($category_id): array {
    $this->db->prepare("SELECT COUNT(*) as count FROM infrastructure WHERE category_id = :category_id OR parent_id = :category_id");
    $this->db->bind('category_id', $category_id);
    $data = $this->db->resultset();
    return array_values($data);
  }

  /* Remove category, check for hosts remaining before removal! */
  public function deleteCategory($category_id): array {
    $this->db->prepare("DELETE FROM infrastructure WHERE category_id = :category_id");
    $this->db->bind('category_id', $category_id);
    $data = $this->db->resultset();
    return array_values($data);
  }
}
