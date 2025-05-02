<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Legacy\Component\Translation\Application\String\Repository;

use WPML\Core\Component\Translation\Application\String\Repository\StringBatchRepositoryInterface;
use WPML\Core\Component\Translation\Application\String\StringException;
use WPML\Core\Port\Persistence\DatabaseWriteInterface;

class StringBatchRepository implements StringBatchRepositoryInterface {

  /**
   * @var DatabaseWriteInterface
   */
  private $databaseWrite;

  /**
   * @var \SitePress
   */
  private $sitepress;


  public function __construct(
    DatabaseWriteInterface $databaseWrite,
    \SitePress $sitepress
  ) {
    $this->databaseWrite = $databaseWrite;
    $this->sitepress     = $sitepress;
  }


  /**
   * @param string $name
   * @param int[]  $stringIds
   *
   * @return int
   * @throws StringException
   *
   */
  public function create( string $name, array $stringIds, string $sourceLanguageCode ): int {
    try {
      $batchId = $this->databaseWrite->insert(
        'icl_translation_batches',
        [
          'batch_name'  => $name,
          'last_update' => date( 'Y-m-d H:i:s' ),
        ]
      );

      $rowset = array_map(
        function ( $stringId ) use ( $batchId ) {
          return [
            'string_id' => $stringId,
            'batch_id'  => $batchId,
          ];
        },
        $stringIds
      );
      $this->databaseWrite->insertMany( 'icl_string_batches', $rowset );

      $this->sitepress->set_element_language_details(
        $batchId,
        'st-batch_strings',
        null,
        $sourceLanguageCode
      );

      return $batchId;
    } catch ( \Throwable $e ) {
      throw new StringException( $e->getMessage(), (int) $e->getCode(), $e );
    }
  }


}
