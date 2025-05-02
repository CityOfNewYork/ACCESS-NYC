<?php

namespace WPML\Core\Component\Translation\Application\Repository;

interface TranslatorNoteRepositoryInterface {


  /**
   * @param int $id
   * @param string $note
   * @return bool
   */
  public function save( int $id, string $note );


}
