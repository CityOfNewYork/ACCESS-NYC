<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Repository;

use WPML\Core\Component\Translation\Application\Repository\TranslatorNoteRepositoryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;

class StringPackageTranslatorNoteRepository implements TranslatorNoteRepositoryInterface {
  const STRING_PACKAGES_TABLE = 'icl_string_packages';


  /**
   * @param int $id
   * @param string $note
   * @return bool
   * @throws DatabaseErrorException
   */
  public function save( int $id, string $note ) {
    $wpdb = $GLOBALS['wpdb'];

    $result = $wpdb->update(
      $wpdb->prefix . self::STRING_PACKAGES_TABLE,
      [ 'translator_note' => $note ],
      [ 'ID' => $id ]
    );

    return $result !== false;
  }


}
