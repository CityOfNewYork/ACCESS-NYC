<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\SaveTranslatorNote;

use WPML\Core\Component\Translation\Application\Repository\Command\SaveTranslatorNoteCommand;
use WPML\Core\Component\Translation\Application\Service\TranslatorNoteService;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\InvalidArgumentException;

class SaveTranslatorNoteController implements EndpointInterface {

  /** @var TranslatorNoteService */
  private $translatorNoteService;


  public function __construct( TranslatorNoteService $translatorNoteService ) {
    $this->translatorNoteService = $translatorNoteService;
  }


  /**
   * @param array<mixed> $requestData
   *
   * @return array|mixed[]
   *
   * @throws InvalidArgumentException The requestData was not valid.
   */
  public function handle( $requestData = null ): array {
    $requestData = $requestData ?: [];

    $itemKind = $requestData['itemKind'] ?? null;
    $itemId = $requestData['itemId'] ?? null;
    $note = $requestData['note'] ?? '';

    if ( $itemKind === null ) {
      throw new InvalidArgumentException( 'Item kind is required.' );
    }

    if ( ! is_string( $itemKind ) ) {
      throw new InvalidArgumentException( 'Item kind must be a string.' );
    }

    if ( $itemId === null ) {
      throw new InvalidArgumentException( 'Item ID is required.' );
    }

    if ( ! is_numeric( $itemId ) ) {
      throw new InvalidArgumentException( 'Item ID must be an integer.' );
    }

    $command = new SaveTranslatorNoteCommand(
      $itemKind,
      (int) $itemId,
      $note
    );
    $result = $this->translatorNoteService->saveTranslatorNote( $command );

    return [ 'success' => $result ];
  }


}
