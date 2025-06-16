<?php
declare(strict_types=1);

namespace App\Application\Actions\TrapMapping;

use Psr\Http\Message\ResponseInterface as Response;

class ViewTrapMappingAction extends TrapMappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $data = $this->getFormData();
    $trapMapping = $this->trapMappingRepository->findOid( $data );
    return $this->respondWithData($trapMapping);
  } // end function
} // end class
