<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Updates;

use WPML\PHP\Exception\Exception;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\MethodType;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\ApiInterface;

class UpdateHandler {
  const ROUTE_ID = 'updates';
  const ROUTE_PATH = '/updates';

  /** @var Endpoint|null */
  private $_endpoint;

  /** @var ApiInterface $api */
  private $api;

  /** @var Repository $repository */
  private $repository;

  /** @var array<string, Update> $updatesToPerform */
  private $updatesToPerform = [];


  public function __construct( ApiInterface $api, Repository $repository ) {
    $this->api = $api;
    $this->repository = $repository;
  }


  /** @return Endpoint */
  public function endpoint() {
    if ( $this->_endpoint === null ) {
      $this->_endpoint = new Endpoint( self::ROUTE_ID, self::ROUTE_PATH );
      $this->_endpoint->setMethod( MethodType::POST );
    }

    return $this->_endpoint;
  }


  /**
   * @param array<string, Update> $updatesToPerform
   * @return void
   */
  public function registerRoute( $updatesToPerform ) {
    $this->updatesToPerform = $updatesToPerform;

    $this->api->registerRoute(
      $this->endpoint(),
      [ $this, 'handle' ],
      [ $this, 'authorisation' ]
    );
  }


  /**
   * @param array<string, mixed> $requestData
   * @return void
   */
  public function handle( $requestData ) {
    if (
      ! isset( $requestData['update'] )
      || ! isset( $this->updatesToPerform[ $requestData['update'] ] )
    ) {
      return;
    }

    $update = $this->updatesToPerform[ $requestData['update'] ];
    try {
      $update->tryOnlyOnce()
        ? $this->repository->setUpdateComplete( $update )
        : $this->repository->setUpdateInProgress( $update );

      $handler = $update->handler();
      $result = $handler->update();

      if ( ! $update->tryOnlyOnce() ) {
        $result
          ? $this->repository->setUpdateComplete( $update )
          : $this->repository->setUpdateFailed( $update );
      }
    } catch ( Exception $e ) {
      if ( ! $update->tryOnlyOnce() ) {
        $this->repository->setUpdateFailed( $update );
      }
    }
  }


  /** @return bool */
  public function authorisation() {
    return $this->api->validateRequest( $this->endpoint()->capability() );
  }


}
