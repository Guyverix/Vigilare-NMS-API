<?php
/*
  This will get information on grouped applications.
  There will likely not be too much traffic on these
  API calls except for frontend UI for the dashboard
  and perhaps managers? looking at the host summary
*/

declare(strict_types=1);

namespace App\Domain\Site;

require __DIR__ . '/../../../app/Database.php';

/*
  In normal cases these functions will match the ones
  defined in your Database in Infrastructure/Persistence/Site/DatabaseSiteRepository.php
*/

interface SiteRepository {
  public function getId($arr): array;
  public function getAllHostnames();
  public function getAllHostnamesJson();
  public function getHostnameFromGroupName($arr): array;
  public function getGroupNamesFromHostname($arr): array;
  public function addGroupName($arr): array;
  public function deleteGroupName($arr): array;
  public function addHostname($arr): array;
  public function deleteHostname($arr): array;
  public function cleanHostname($arr): array;
  public function findSiteInvalid($arr): array;
}
