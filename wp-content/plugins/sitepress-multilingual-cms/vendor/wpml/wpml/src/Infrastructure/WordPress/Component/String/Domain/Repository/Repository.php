<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Infrastructure\WordPress\Component\String\Domain\Repository;

use WPML\Core\Port\Persistence\DatabaseWriteInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\String\Domain\Repository\RepositoryInterface;
use WPML\Core\SharedKernel\Component\String\Domain\StringEntity;
use WPML\PHP\Exception\InvalidArgumentException;
use WPML\PHP\Exception\InvalidItemIdException;

/**
 * @phpstan-type StringRow array{
 *   id: int,
 *   language: string,
 *   context: string,
 *   name: string,
 *   value: string,
 *   status: int|string,
 *   wordCount: int|string
 * }
 */
class Repository implements RepositoryInterface {

  /** @phpstan-var  QueryHandlerInterface<int, StringRow> $queryHandler */
  private $queryHandler;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var DatabaseWriteInterface */
  private $dbWriter;


  /**
   * @phpstan-param  QueryHandlerInterface<int, StringRow> $queryHandler
   *
   * @param QueryPrepareInterface                          $queryPrepare
   * @param DatabaseWriteInterface                         $dbWriter
   */
  public function __construct(
    QueryHandlerInterface $queryHandler,
    QueryPrepareInterface $queryPrepare,
    DatabaseWriteInterface $dbWriter
  ) {
    $this->queryHandler = $queryHandler;
    $this->queryPrepare = $queryPrepare;
    $this->dbWriter     = $dbWriter;
  }


  /**
   * @param int $stringId
   *
   * @return StringEntity
   * @throws InvalidItemIdException
   */
  public function get( int $stringId ): StringEntity {
    $sql = "
        SELECT
            {$this->getColumns()}
        FROM {$this->queryPrepare->prefix()}icl_strings s
        WHERE s.id = %d
    ";

    $sql = $this->queryPrepare->prepare( $sql, $stringId );

    try {
      $row = $this->queryHandler->queryOne( $sql );
      if ( ! $row ) {
        throw new InvalidItemIdException();
      }
    } catch ( DatabaseErrorException $e ) {
      throw new InvalidItemIdException();
    }

    return $this->buildDto( $row );
  }


  public function getBelongingToPackage( int $packageId ): array {
    $sql = "
        SELECT
          {$this->getColumns()}
        FROM {$this->queryPrepare->prefix()}icl_strings s
        WHERE s.string_package_id = %d
    ";

    $sql = $this->queryPrepare->prepare( $sql, $packageId );
    try {
      $rowset = $this->queryHandler->query( $sql )->getResults();
    } catch ( DatabaseErrorException $e ) {
      return [];
    }

    $result = [];
    foreach ( $rowset as $row ) {
      $result[] = $this->buildDto( $row );
    }

    return $result;
  }


  /**
   * @param StringRow $rawData
   *
   * @return StringEntity
   */
  private function buildDto( array $rawData ): StringEntity {
    return new StringEntity(
      $rawData['id'],
      $rawData['language'],
      $rawData['context'],
      $rawData['name'],
      $rawData['value'],
      (int) $rawData['status'],
      (int) $rawData['wordCount']
    );

  }


  private function getColumns(): string {
    return "
      s.id,
      s.language,
      s.context,
      s.name,
      s.value,
      s.status,
      s.word_count as wordCount
    ";
  }


  /**
   * @param int    $stringId
   * @param string $field
   * @param mixed  $value
   *
   * @throws InvalidArgumentException
   */
  public function updateField( int $stringId, string $field, $value ) {
    $editableFields = [ 'status', 'word_count' ];
    if ( ! in_array( $field, $editableFields, true ) ) {
      throw new InvalidArgumentException( sprintf( 'Field %s is not editable', $field ) );
    }

    try {
      $this->dbWriter->update(
        'icl_strings',
        [ $field => $value ],
        [ 'id' => $stringId ]
      );
    } catch ( DatabaseErrorException $e ) {
      throw new InvalidArgumentException( sprintf( 'Failed to update field %s for string %d', $field, $stringId ) );
    }
  }


}
