<?php

namespace WPML\Infrastructure\WordPress\Component\StringPackage\Application\Query;

use WPML\Core\Component\StringPackage\Application\Query\Dto\PackageDefinitionDto;
use WPML\Core\Component\StringPackage\Application\Query\PackageDefinitionQueryInterface;

class PackageDefinitionQuery implements PackageDefinitionQueryInterface {


  /**
   * @return array<string, PackageDefinitionDto>
   */
  public function getInfoList(): array {
    $packageDefinitions = [];

    foreach ( $this->callFilter() as $slug => $info ) {
      $packageDefinitions[ $slug ] = new PackageDefinitionDto(
        $info['title'],
        $info['slug'],
        $info['plural']
      );
    }

    return $packageDefinitions;
  }


  public function isPackageOnTheList( string $packageKindSlug ): bool {
    $list = $this->getNamesList();

    // There is inconsistency with "Blocks" package type.
    // It is returned as "Blocks" by the hook, while we use "blocks" internally in various places.
    $lowercaseList = array_map( 'strtolower', $list );

    return in_array( strtolower( $packageKindSlug ), $lowercaseList, true );
  }


  /**
   * @return string[]
   */
  public function getNamesList(): array {
    return array_keys( $this->callFilter() );
  }


  /**
   * Filter active string package kinds.
   * 3rd party code can hook to this filter to register support for its string packages.
   * Return value should be similar to:
   * [
   *    'my-awesome-package-slug' => [
   *        'title'  => 'My Awesome Package',
   *        'slug'   => 'my-awesome-package-slug',
   *        'plural' => 'My Awesome Packages',
   *    ]
   * ]
   *
   * @return array<string, array{title: string, slug: string, plural: string}> $packages
   * @since 4.7.0
   *
   */
  private function callFilter(): array {
    return \apply_filters( 'wpml_active_string_package_kinds', [] );
  }


}
