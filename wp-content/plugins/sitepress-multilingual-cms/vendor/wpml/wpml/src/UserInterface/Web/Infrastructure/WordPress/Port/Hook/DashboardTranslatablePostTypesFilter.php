<?php
namespace WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook;

use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook\DashboardTranslatablePostTypesFilterInterface;

class DashboardTranslatablePostTypesFilter implements DashboardTranslatablePostTypesFilterInterface {
  const NAME = 'wpml_tm_dashboard_translatable_types';


  /**
   * @param array<string, mixed> $postTypes
   * @return array<string, mixed>
   */
  public function filter( array $postTypes ) {
    // We manually remove 'media' section.
    if ( isset( $postTypes['attachment'] ) ) {
      unset( $postTypes['attachment'] );
    }
    return apply_filters( static::NAME, $postTypes );
  }


}
