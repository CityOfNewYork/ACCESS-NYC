<?php

namespace WPML\Core\Component\Translation\Application\Service;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Event\TranslationsSentEvent;
use WPML\Core\Component\Translation\Application\Service\TranslationService\BatchBuilder\BatchBuilderInterface;
use WPML\Core\Component\Translation\Application\Service\TranslationService\Dto\ResultDto;
use WPML\Core\Component\Translation\Application\Service\TranslationService\ResultBuilder;
use WPML\Core\Component\Translation\Application\Service\TranslationService\TranslationServiceException;
use WPML\Core\Component\Translation\Application\String\StringBatchToStringsTranslationsMapper;
use WPML\Core\Component\Translation\Domain\Sender\DuplicationSenderInterface;
use WPML\Core\Component\Translation\Domain\Sender\SendBatchException;
use WPML\Core\Component\Translation\Domain\Sender\TranslationSenderInterface;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Port\Event\DispatcherInterface;
use WPML\PHP\Exception\InvalidArgumentException;


class TranslationService {

  /** @var BatchBuilderInterface */
  private $batchBuilder;

  /** @var TranslationSenderInterface */
  private $translationSender;

  /** @var DuplicationSenderInterface */
  private $duplicationSender;

  /** @var StringBatchToStringsTranslationsMapper */
  private $stringBatchToStringsTranslationsMapper;

  /** @var ResultBuilder */
  private $resultBuilder;

  /** @var DispatcherInterface */
  private $eventDispatcher;


  public function __construct(
    BatchBuilderInterface $batchBuilder,
    TranslationSenderInterface $translationSender,
    DuplicationSenderInterface $duplicationSender,
    StringBatchToStringsTranslationsMapper $stringBatchToStringsTranslationsMapper,
    ResultBuilder $resultBuilder,
    DispatcherInterface $eventDispatcher
  ) {
    $this->batchBuilder                           = $batchBuilder;
    $this->translationSender                      = $translationSender;
    $this->duplicationSender                      = $duplicationSender;
    $this->stringBatchToStringsTranslationsMapper = $stringBatchToStringsTranslationsMapper;
    $this->resultBuilder                          = $resultBuilder;
    $this->eventDispatcher                        = $eventDispatcher;
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return ResultDto
   * @throws TranslationServiceException
   * @throws InvalidArgumentException
   */
  public function send( SendToTranslationDto $sendToTranslationDto ): ResultDto {
    list( $translationBatch, $duplicationBatch, $ignoredElements ) =
      $this->batchBuilder->build( $sendToTranslationDto );

    $duplicatedTranslations = $this->byDuplicate( $duplicationBatch );
    $regularTranslations    = $this->byTranslation( $translationBatch );

    $translations = array_merge( $duplicatedTranslations, $regularTranslations );

    $result = $this->resultBuilder->build( $translations, $ignoredElements );
    $this->eventDispatcher->dispatch( new TranslationsSentEvent( $result ) );

    return $result;
  }


  /**
   * @param DuplicationBatch|null $duplicationBatch
   *
   * @return Translation[]
   */
  private function byDuplicate( DuplicationBatch $duplicationBatch = null ): array {
    if ( $duplicationBatch ) {
      return $this->duplicationSender->send( $duplicationBatch );
    }

    return [];
  }


  /**
   * @param TranslationBatch|null $translationBatch
   *
   * @return Translation[]
   * @throws TranslationServiceException
   */
  private function byTranslation( TranslationBatch $translationBatch = null ): array {
    if ( $translationBatch ) {
      try {

        $translations = $this->translationSender->send( $translationBatch );

        return $this->stringBatchToStringsTranslationsMapper->map( $translations );

      } catch ( SendBatchException $e ) {
        $this->translationSender->rollback( $translationBatch );

        throw new TranslationServiceException( $e->getMessage(), $e->getCode(), $e );
      }
    }

    return [];
  }


}
