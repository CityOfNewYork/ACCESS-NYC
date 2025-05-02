<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Repository;

use WPML\Core\Component\Translation\Application\Repository\TranslatorNoteRepositoryInterface;

class PostTranslatorNoteRepository implements TranslatorNoteRepositoryInterface {
  const TRANSLATOR_NOTE_META_KEY = '_icl_translator_note';


  /**
   * @param int $id
   * @param string $note
   * @return bool
   */
  public function save( int $id, string $note ) {
    $result = update_post_meta( $id, self::TRANSLATOR_NOTE_META_KEY, $note );

    return $result !== false;
  }


}
