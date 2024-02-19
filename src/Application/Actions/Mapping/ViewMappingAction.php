<?php
declare(strict_types=1);

namespace App\Application\Actions\Mapping;

use Psr\Http\Message\ResponseInterface as Response;

class ViewMappingAction extends MappingAction
{
  /**
   * {@inheritdoc}
   */
  protected function action(): Response {
    $data = $this->getFormData();
    $mapping = $this->mappingRepository->findOid( $data );
    return $this->respondWithData($mapping);
  } // end function
} // end class
