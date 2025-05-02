<?php

namespace WPML\Core\Component\Translation\Application\Service\Event;

use WPML\Core\Component\Translation\Application\Service\TranslationService\Dto\ResultDto;

class TranslationsSentEvent extends \WPML\Core\Port\Event\Event {


  public function __construct( ResultDto $resultDto ) {
    parent::__construct( 'wpml_translations_sent_from_dashboard', [ $resultDto ] );
  }


}
