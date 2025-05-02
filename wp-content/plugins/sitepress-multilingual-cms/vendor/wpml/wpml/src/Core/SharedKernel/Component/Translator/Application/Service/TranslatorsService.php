<?php

namespace WPML\Core\SharedKernel\Component\Translator\Application\Service;

use WPML\Core\SharedKernel\Component\Translator\Application\Service\Dto\TranslatorDto;
use WPML\Core\SharedKernel\Component\Translator\Domain\Query\TranslatorsQueryInterface;
use WPML\Core\SharedKernel\Component\Translator\Domain\Translator;

class TranslatorsService {

  /** @var TranslatorsQueryInterface */
  private $translatorsQuery;


  public function __construct( TranslatorsQueryInterface $translatorsQuery ) {
    $this->translatorsQuery = $translatorsQuery;
  }


  /**
   * @return TranslatorDto[]
   */
  public function get(): array {
    return array_map(
      function ( Translator $translator ): TranslatorDto {
        return TranslatorDtoMapper::map( $translator );
      },
      $this->translatorsQuery->get()
    );
  }


  /**
   * @param int $id
   *
   * @return TranslatorDto|null
   */
  public function getById( int $id ) {
    $translator = $this->translatorsQuery->getById( $id );

    if ( ! $translator ) {
      return null;
    }

    return TranslatorDtoMapper::map( $translator );
  }


  /**
   * @return TranslatorDto|null
   */
  public function getCurrentlyLoggedId() {
    $currentlyLoggedIn = $this->translatorsQuery->getCurrentlyLoggedId();

    if ( ! $currentlyLoggedIn ) {
      return null;
    }

    return TranslatorDtoMapper::map( $currentlyLoggedIn );
  }


}
