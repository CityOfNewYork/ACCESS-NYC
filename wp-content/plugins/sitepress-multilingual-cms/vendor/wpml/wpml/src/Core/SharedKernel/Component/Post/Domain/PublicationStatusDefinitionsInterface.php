<?php

namespace WPML\Core\SharedKernel\Component\Post\Domain;

interface PublicationStatusDefinitionsInterface {


  public function isPublished( string $status ) : bool;


  public function isPublishable( string $status ) : bool;


  /**
   * @param ?string $statusBefore
   */
  public function gotPublished( string $status, $statusBefore ) : bool;


}
