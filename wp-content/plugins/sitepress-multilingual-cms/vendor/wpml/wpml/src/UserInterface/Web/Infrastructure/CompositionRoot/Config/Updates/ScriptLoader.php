<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\ApiInterface;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\PageInterface;

class ScriptLoader {
  const SCRIPT_NAME = 'wpml-updates';
  const SCRIPT_SRC = 'public/js/updates.js';
  const SCRIPT_JS_VAR = 'wpmlUpdates';

  /** @var ApiInterface $api */
  private $api;

  /** @var PageInterface $page */
  private $page;


  public function __construct( ApiInterface $api, PageInterface $page ) {
    $this->api = $api;
    $this->page = $page;
  }


  /**
   * @param array<string> $idsOfUpdatesToPerform
   * @param Endpoint $endpoint
   *
   * @return void
   */
  public function loadScript( $idsOfUpdatesToPerform, $endpoint ) {
    if ( empty( $idsOfUpdatesToPerform ) ) {
      return;
    }

    $script = new Script( self::SCRIPT_NAME );
    $script->setSrc( self::SCRIPT_SRC );
    $script->setDependencies( [ 'wpml-node-modules' ] );

    $this->page->loadScript( $script );
    $this->page->provideDataForScript(
      $script,
      self::SCRIPT_JS_VAR,
      [
        'updates' => $idsOfUpdatesToPerform,
        'nonce' => $this->api->nonce(),
        'route' => $this->api->getFullUrl( $endpoint ),
      ]
    );
  }


}
