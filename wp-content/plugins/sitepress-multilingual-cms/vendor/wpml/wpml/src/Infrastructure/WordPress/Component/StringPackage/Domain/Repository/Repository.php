<?php

namespace WPML\Infrastructure\WordPress\Component\StringPackage\Domain\Repository;

use WPML\Core\Port\Persistence\DatabaseWriteInterface;
use WPML\Core\SharedKernel\Component\StringPackage\Domain\Repository\RepositoryInterface;
use WPML\PHP\Exception\InvalidArgumentException;

class Repository implements RepositoryInterface {

  /** @var DatabaseWriteInterface */
  private $dbWriter;


  public function __construct( DatabaseWriteInterface $dbWriter ) {
    $this->dbWriter = $dbWriter;
  }


  public function updateField( int $packageId, string $field, $value ) {
    $editableFields = [ 'word_count' ];
    if ( ! in_array( $field, $editableFields, true ) ) {
      throw new InvalidArgumentException( sprintf( 'Field %s is not editable', $field ) );
    }

    try {
      $this->dbWriter->update(
        'icl_string_packages',
        [ $field => $value ],
        [ 'id' => $packageId ]
      );
    } catch ( \Throwable $e ) {
      throw new InvalidArgumentException(
        sprintf( 'Failed to update field %s for package %d', $field, $packageId )
      );
    }
  }


}
