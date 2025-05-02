<?php

namespace WPML\Core\Component\Translation\Application\Update\Database\TranslationStatus;

use WPML\Core\Port\Persistence\DatabaseAlterInterface;
use WPML\Core\Port\Update\UpdateInterface;
use WPML\PHP\Exception\Exception;

class AddIndexForReviewStatus implements UpdateInterface {

  /** @var DatabaseAlterInterface */
  private $db;


  public function __construct( DatabaseAlterInterface $db ) {
    $this->db = $db;
  }


  public function update() {
    try {
      return $this->db->addIndex( 'icl_translation_status', 'review_status' );
    } catch ( Exception $e ) {
      return false;
    }
  }


}
