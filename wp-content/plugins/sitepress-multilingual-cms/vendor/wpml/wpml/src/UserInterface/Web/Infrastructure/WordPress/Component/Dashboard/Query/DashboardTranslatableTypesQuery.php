<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Component\Dashboard\Query;

use WPML\Legacy\Component\Post\Application\Query\TranslatableTypesQuery;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook\DashboardTranslatablePostTypesFilterInterface;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Query\DashboardTranslatableTypesQueryInterface;

/**
 * @phpcs:disable Glingener.Classes.ForbiddenSitePressClasses.Found
 * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
 */
class DashboardTranslatableTypesQuery
  extends TranslatableTypesQuery
  implements DashboardTranslatableTypesQueryInterface {


  public function __construct(
    \SitePress $sitepress,
    DashboardTranslatablePostTypesFilterInterface $postTypeFilter
  ) {
    parent::__construct( $sitepress, $postTypeFilter );
  }


}
