<?php

declare(strict_types=1);

namespace App\Application\Actions\Graphite;
use Psr\Http\Message\ResponseInterface as Response;

class CatchGraphiteAction extends GraphiteAction {
  protected function action(): Response {
  $result[]="Graphite API";
  $result[]="All functions must be called via GET and the hostname that is in the database";
  $result[]="http(s)://URL/graphite/<HOSTNAME>";
  $result[]="Expected returns are in a JSON array";
  $result[]="Next iteration will support pure HTML as an optional return";
  return $this->respondWithData($result);
  }
}
