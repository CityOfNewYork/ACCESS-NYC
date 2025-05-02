<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Core\Component\Translation\Application\String;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\TranslationService\BatchBuilder\BatchBuilderInterface;
use WPML\Core\Component\Translation\Application\String\Repository\StringBatchRepositoryInterface;
use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Element;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TargetLanguage;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\IgnoredElement;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\PHP\Exception\InvalidArgumentException;
use function WPML\PHP\Logger\error;
use function WPML\PHP\partition;


class StringBatchBuilder implements BatchBuilderInterface {

  /**
   * @var BatchBuilderInterface
   */
  private $batchBuilder;

  /**
   * @var StringBatchRepositoryInterface
   */
  private $stringBatchRepository;


  public function __construct(
    BatchBuilderInterface $batchBuilder,
    StringBatchRepositoryInterface $stringBatchRepository
  ) {
    $this->batchBuilder          = $batchBuilder;
    $this->stringBatchRepository = $stringBatchRepository;
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return array{0: TranslationBatch|null, 1: DuplicationBatch|null, 2: IgnoredElement[]}
   * @throws InvalidArgumentException
   */
  public function build( SendToTranslationDto $sendToTranslationDto ): array {
    list( $translationBatch, $duplicationBatch, $ignoredElements ) =
      $this->batchBuilder->build( $sendToTranslationDto );

    if ( $translationBatch ) {
      $translationBatch = $this->groupStringsIntoBatches( $translationBatch );
    }

    return [ $translationBatch, $duplicationBatch, $ignoredElements ];
  }


  private function groupStringsIntoBatches( TranslationBatch $translationBatch ): TranslationBatch {
    $targetLanguages = [];

    foreach ( $translationBatch->getTargetLanguages() as $targetLanguage ) {
      list( $stringElements, $otherElements ) =
        $this->partitionElementsOnStringAndOthers( $targetLanguage->getElements() );

      if ( count( $stringElements ) ) {
        $stringIds = array_values(
          array_map(
            function ( $stringElement ) {
              return $stringElement->getElementId();
            },
            $stringElements
          )
        );

        try {
          $batchId = $this->stringBatchRepository->create(
            $translationBatch->getBatchName() . '|string|' . $targetLanguage->getLanguageCode(),
            $stringIds,
            $translationBatch->getSourceLanguageCode()
          );

          $stringBatchElement = new Element(
            $batchId,
            TranslationType::stringBatch(),
            $translationBatch->getSourceLanguageCode()
          );

          $targetLanguages[] = new TargetLanguage(
            $targetLanguage->getLanguageCode(),
            $targetLanguage->getMethod(),
            array_merge( $otherElements, [ $stringBatchElement ] )
          );
        } catch ( StringException $e ) {
          error( 'Error creating string batch: ' . $e->getMessage() );

          $targetLanguages[] = new TargetLanguage(
            $targetLanguage->getLanguageCode(),
            $targetLanguage->getMethod(),
            $otherElements
          );
        }
      } else {
        $targetLanguages[] = $targetLanguage;
      }
    }

    return $translationBatch->copyWithNewTargetLanguages( $targetLanguages );
  }


  /**
   * @param Element[] $elements
   *
   * @return array{0: Element[], 1: Element[]}
   */
  private function partitionElementsOnStringAndOthers( array $elements ) {
    return partition(
      $elements,
      function ( Element $element ) {
        return $element->getType()->get() === TranslationType::STRING;
      }
    );
  }


}
