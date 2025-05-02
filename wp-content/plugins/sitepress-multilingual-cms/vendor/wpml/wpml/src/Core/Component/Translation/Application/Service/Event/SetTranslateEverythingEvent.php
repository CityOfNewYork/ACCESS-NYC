<?php

namespace WPML\Core\Component\Translation\Application\Service\Event;

class SetTranslateEverythingEvent extends \WPML\Core\Port\Event\Event {


  /**
   * @param bool $enabled
   * @param array{
   *   translateExisting?: bool,
   *   reviewMode?: string|null
   * }           $options
   */
  public function __construct( bool $enabled, array $options = [] ) {
    $options = array_merge( [ 'translateExisting' => false, 'reviewMode' => null ], $options );

    parent::__construct( 'wpml_set_translate_everything', [ $enabled, $options ] );
  }


}
