<?php
declare(strict_types=1);

namespace App\Domain\Infrastructure;

/*
   Logic for infrastructure:
   View existing entries
   create new products (hosts)
   update new products and categories
   delete existing products and categories
*/

interface InfrastructureRepository {
    public function findChildren(string $parent): array;               // Retrieve all child hosts for category given
    public function findChildrenOfParent(string $category_id): array;  // this is the list of hosts that map to the above categories
    public function findOrphans(): array;                 // this is the list of hosts that DO NOT map to the above categories, or are set as "unknown"

    public function newHost(array $detail): array;                     // category_id, product_name (hostname)
    public function updateHost(array $detail): array;                  // product_id, category_id, product_name (hostname)
    public function deleteHost(array $detail): array;                  // product_name (hostname)

    public function findCategory(): array;                             // return all categories and ID values
    public function newCategory(array $detail): array;                 // parent_id, category_name
    public function updateCategory(array $detail): array;              // parent_id, category_name

    public function validateCategoryBeforeDelete($category_id): array; // Check if parents or child categoires will be affected by deletions
    public function validateHostBeforeDelete($category_id): array;     // Check if any hosts remain attached to the category
    public function deleteCategory(array $detail): array;              // category_id, parent_id (Never delete parent_id 0!)
}
