<?php

namespace WPML\Core\Component\Translation\Application\Service;

use WPML\Core\Component\Translation\Application\Repository\Command\SaveTranslatorNoteCommand;
use WPML\Core\Component\Translation\Application\Repository\TranslatorNoteRepositoryInterface;
use WPML\PHP\Exception\InvalidArgumentException;

class TranslatorNoteService {

  /** @var TranslatorNoteRepositoryInterface */
  private $postTranslatorNoteRepo;

  /** @var TranslatorNoteRepositoryInterface */
  private $stringPackageTranslatorNoteRepo;


  public function __construct(
    TranslatorNoteRepositoryInterface $postTranslatorNoteRepo,
    TranslatorNoteRepositoryInterface $stringPackageTranslatorNoteRepo
  ) {
    $this->postTranslatorNoteRepo = $postTranslatorNoteRepo;
    $this->stringPackageTranslatorNoteRepo = $stringPackageTranslatorNoteRepo;
  }


  /**
   * @param SaveTranslatorNoteCommand $command
   * @return bool
   * @throws InvalidArgumentException
   */
  public function saveTranslatorNote( SaveTranslatorNoteCommand $command ) {
    $saveResult = false;

    if ( $command->getItemKind() === 'package' ) {
      $saveResult = $this->stringPackageTranslatorNoteRepo->save(
        $command->getItemId(),
        $command->getNote()
      );
    } elseif ( $command->getItemKind() === 'post' ) {
      $saveResult =  $this->postTranslatorNoteRepo->save(
        $command->getItemId(),
        $command->getNote()
      );
    } else {
      throw new InvalidArgumentException( 'No Translator note support for this itemKind: ' . $command->getItemKind() );
    }

    return $saveResult;
  }


}
