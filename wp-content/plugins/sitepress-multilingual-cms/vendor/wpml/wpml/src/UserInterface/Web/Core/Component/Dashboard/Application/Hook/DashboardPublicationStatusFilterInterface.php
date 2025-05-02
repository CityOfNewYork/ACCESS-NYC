<?php
namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook;

use WPML\Core\Component\Post\Application\Query\Dto\PublicationStatusDto;

interface DashboardPublicationStatusFilterInterface {


  /**
   * @param PublicationStatusDto[] $publicationStatusDtos
   * @return PublicationStatusDto[]
   */
  public function filterByDto( array $publicationStatusDtos );


}
