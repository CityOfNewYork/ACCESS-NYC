<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config;

use WPML\ConfigEventsInterface;
use WPML\DicInterface;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\Event\Item\WordCount\Events as WordCountEvents;


/**
 * Class ConfigEvents
 *
 * This is only for events which are triggered by a 3rd party (WordPress other
 * plugin) AND which are triggering the start of the WPML code.
 *
 * If some already loaded coed (like a Page) must react on an event, the event
 * registration happens on that page and NOT HERE.
 *
 * This approach (using the DIC here) is used to load as less code as possible.
 *
 * phpcs:ignoreFile
 * Full of WP stuff.
 *
 */
class ConfigEvents implements ConfigEventsInterface {

  /**
    * @var DicInterface $dic
    */
  private $dic;


  public function __construct( DicInterface $dic ) {
    $this->dic = $dic;
  }


  /** @return void */
  public function loadEvents() {
    new Event\Translation\Links\ItemUpdateEvent( $this->dic );
    new Event\Translation\Posts\PostInsertedEvent( $this->dic );
    new Event\Translation\Posts\PageBuilderEditWarningEvent( $this->dic );
    new Event\Translation\StartUsingDashboardBanner\Events( $this->dic );
    new WordCountEvents( $this->dic );
  }


}
