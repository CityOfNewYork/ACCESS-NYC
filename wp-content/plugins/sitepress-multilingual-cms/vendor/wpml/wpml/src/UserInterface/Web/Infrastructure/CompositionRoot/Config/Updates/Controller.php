<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates;

use WPML\Core\Port\PluginInterface;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\ApiInterface;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\UpdatesHandlerInterface;

class Controller implements UpdatesHandlerInterface {

  /** @var ApiInterface $api */
  private $api;

  /** @var Repository $repository */
  private $repository;

  /** @var ScriptLoader $scriptLoader */
  private $scriptLoader;

  /** @var UpdateHandler $updateHandler */
  private $updateHandler;

  /** @var PluginInterface $plugin */
  private $plugin;


  public function __construct(
    ApiInterface $api,
    Repository $repository,
    ScriptLoader $scriptLoader,
    UpdateHandler $updateHandler,
    PluginInterface $plugin
  ) {
    $this->api = $api;
    $this->repository = $repository;
    $this->scriptLoader = $scriptLoader;
    $this->updateHandler = $updateHandler;
    $this->plugin = $plugin;
  }


  /**
   * @param array<string, Update> $allUpdates
   * @return void
   */
  public function prepareUpdates( $allUpdates ) {
    if ( ! $this->plugin->isSetupComplete() ) {
      // No updates before setup is complete.
      // Currently this is redundant because wpml/wpml is only loaded
      // after the setup is complete, but that might change in the future.
      return;
    }

    if ( $this->api->isRestRequest() ) {
      $this->onRest( $allUpdates );
      return;
    }

    $this->onAdmin( $allUpdates );
  }


  /**
   * @param array<string, Update> $allUpdates
   * @return void
   */
  private function onRest( $allUpdates ) {
    $updatesToPerform = $this->repository->getUpdatesToPerform( $allUpdates );
    $this->updateHandler->registerRoute( $updatesToPerform );
  }


  /**
   * @param array<string, Update> $allUpdates
   * @return void
   */
  private function onAdmin( $allUpdates ) {
    $updatesToPerform = $this->repository->getUpdatesToPerform( $allUpdates );
    $this->scriptLoader->loadScript(
      array_keys( $updatesToPerform ),
      $this->updateHandler->endpoint()
    );
  }


}
