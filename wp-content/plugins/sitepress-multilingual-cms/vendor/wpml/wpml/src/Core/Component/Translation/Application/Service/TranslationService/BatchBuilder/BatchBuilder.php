<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationService\BatchBuilder;

use WPML\Core\Component\Translation\Application\Query\ItemLanguageQueryInterface;
use WPML\Core\Component\Translation\Application\Query\TranslationQueryInterface;
use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Dto\TargetLanguageMethodDto;
use WPML\Core\Component\Translation\Domain\HowToHandleExistingTranslationType;
use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationBatch\DuplicationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Element;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TargetLanguage;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\IgnoredElement;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\ValidatorInterface;
use WPML\Core\Component\Translation\Domain\TranslationMethod\AutomaticMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\DuplicateMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\LocalTranslatorMethod;
use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationMethodInterface;
use WPML\Core\Component\Translation\Domain\TranslationMethod\TranslationServiceMethod;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;
use WPML\PHP\DateTime;
use WPML\PHP\Exception\InvalidArgumentException;

class BatchBuilder implements BatchBuilderInterface {

  /** @var ValidatorInterface */
  private $translationValidator;

  /** @var TranslationQueryInterface */
  private $translationQuery;

  /** @var ItemLanguageQueryInterface */
  private $itemLanguageQuery;


  public function __construct(
    ValidatorInterface $translationValidator,
    TranslationQueryInterface $translationQuery,
    ItemLanguageQueryInterface $itemLanguageQuery
  ) {
    $this->translationValidator   = $translationValidator;
    $this->translationQuery       = $translationQuery;
    $this->itemLanguageQuery      = $itemLanguageQuery;
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return array{0: TranslationBatch|null, 1: DuplicationBatch|null, 2: IgnoredElement[]}
   * @throws InvalidArgumentException
   */
  public function build( SendToTranslationDto $sendToTranslationDto ): array {
    $elements = $this->buildElements( $sendToTranslationDto );

    $duplicationBatch = $this->buildDuplicationBatch( $sendToTranslationDto, $elements );
    list( $translationBatch, $ignoredElements ) = $this->buildTranslationBatch( $sendToTranslationDto, $elements );

    return [ $translationBatch, $duplicationBatch, $ignoredElements ];
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return Element[]
   */
  private function buildElements( SendToTranslationDto $sendToTranslationDto ): array {
    $groupedElementLanguages = $this->getGroupedElementLanguages( $sendToTranslationDto );

    return array_merge(
      $this->mapElementsOfGivenType(
        $groupedElementLanguages,
        TranslationType::post(),
        $sendToTranslationDto->getPosts()
      ),
      $this->mapElementsOfGivenType(
        $groupedElementLanguages,
        TranslationType::package(),
        $sendToTranslationDto->getPackages()
      ),
      $this->mapElementsOfGivenType(
        $groupedElementLanguages,
        TranslationType::string(),
        $sendToTranslationDto->getStrings()
      )
    );
  }


  /**
   * @param array<string, array<int, string>> $groupedElementLanguages
   * @param TranslationType                   $type
   * @param int[]                             $elementIds
   *
   * @return Element[]
   */
  private function mapElementsOfGivenType(
    array $groupedElementLanguages,
    TranslationType $type,
    array $elementIds
  ): array {
    if ( ! count( $elementIds ) ) {
      return [];
    }

    $elementTranslations = $this->groupTranslationByOriginalElementId(
      $this->translationQuery->getManyByElementIds(
        $type,
        $elementIds
      )
    );

    $elements = [];

    foreach ( $elementIds as $elementId ) {
      $elements[] = new Element(
        $elementId,
        $type,
        $groupedElementLanguages[ $type->get() ][ $elementId ] ?? 'en',
        $elementTranslations[ $elementId ] ?? []
      );
    }

    return $elements;
  }


  /**
   * @param Translation[] $translations
   *
   * @return array<int, Translation[]>
   */
  private function groupTranslationByOriginalElementId( array $translations ): array {
    $groupedTranslations = [];

    foreach ( $translations as $translation ) {
      $elementId                           = $translation->getOriginalElementId();
      $groupedTranslations[ $elementId ][] = $translation;
    }

    return $groupedTranslations;
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return array<string, array<int, string>>
   */
  private function getGroupedElementLanguages( SendToTranslationDto $sendToTranslationDto ): array {
    $items = [];

    foreach ( $sendToTranslationDto->getPosts() as $postId ) {
      $items[] = [
        'type'   => TranslationType::post(),
        'itemId' => $postId
      ];
    }

    foreach ( $sendToTranslationDto->getPackages() as $packageId ) {
      $items[] = [
        'type'   => TranslationType::package(),
        'itemId' => $packageId
      ];
    }

    foreach ( $sendToTranslationDto->getStrings() as $stringId ) {
      $items[] = [
        'type'   => TranslationType::string(),
        'itemId' => $stringId
      ];
    }

    $itemLanguages = $this->itemLanguageQuery->getManyOriginalLanguagesOfItems( $items );

    $groupedLanguages = [];

    foreach ( $itemLanguages as $itemLanguage ) {
      $groupedLanguages[ $itemLanguage['type']->get() ][ $itemLanguage['itemId'] ] = $itemLanguage['language'];
    }

    return $groupedLanguages;
  }


  /**
   * @param TargetLanguageMethodDto $targetLanguagesMethod
   *
   * @return TranslationMethodInterface
   * @throws InvalidArgumentException
   */
  private function mapTargetLanguageMethod(
    TargetLanguageMethodDto $targetLanguagesMethod
  ): TranslationMethodInterface {
    switch ( $targetLanguagesMethod->getTranslationMethod() ) {
      case TargetLanguageMethodType::DUPLICATE:
        $method = new DuplicateMethod();
        break;
      case TargetLanguageMethodType::LOCAL_TRANSLATOR:
        $method = new LocalTranslatorMethod(
          $targetLanguagesMethod->getTranslatorId() ?? 0,
          $targetLanguagesMethod->getTargetLanguageCode()
        );
        break;
      case TargetLanguageMethodType::TRANSLATION_SERVICE:
        $method = new TranslationServiceMethod(
          $targetLanguagesMethod->getTranslatorId() ?? 0
        );
        break;
      case TargetLanguageMethodType::AUTOMATIC:
        $method = new AutomaticMethod();
        break;
      default:
        throw new InvalidArgumentException( 'Invalid translation method!' );
    }

    return $method;
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   * @param Element[]            $elements
   *
   * @return DuplicationBatch|null
   */
  private function buildDuplicationBatch( SendToTranslationDto $sendToTranslationDto, array $elements ) {
    $targetLanguages = $this->getTargetLanguagesForDuplication( $sendToTranslationDto );
    if ( ! count( $targetLanguages ) ) {
      return null;
    }

    $postElements = array_filter(
      $elements,
      function ( Element $element ) {
        return $element->getType()->get() === TranslationType::POST;
      }
    );

    $postIds = array_map(
      function ( Element $element ) {
        return $element->getElementId();
      },
      $postElements
    );

    if ( ! count( $postIds ) ) {
      return null;
    }

    return new DuplicationBatch(
      $sendToTranslationDto->getBatchName(),
      $sendToTranslationDto->getSourceLanguageCode(),
      $targetLanguages,
      $postIds
    );
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   *
   * @return string[]
   */
  private function getTargetLanguagesForDuplication( SendToTranslationDto $sendToTranslationDto ): array {
    $filtered = array_filter(
      $sendToTranslationDto->getTargetLanguageMethods(),
      function ( TargetLanguageMethodDto $targetLanguageMethodDto ) {
        return $targetLanguageMethodDto->getTranslationMethod() === TargetLanguageMethodType::DUPLICATE;
      }
    );

    return array_map(
      function ( TargetLanguageMethodDto $targetLanguageMethodDto ) {
        return $targetLanguageMethodDto->getTargetLanguageCode();
      },
      $filtered
    );
  }


  /**
   * @param SendToTranslationDto $sendToTranslationDto
   * @param Element[]            $elements
   *
   * @return array{0: TranslationBatch|null, 1: IgnoredElement[]}
   * @throws InvalidArgumentException
   */
  private function buildTranslationBatch( SendToTranslationDto $sendToTranslationDto, array $elements ): array {
    $translationMethods = [];
    foreach ( $sendToTranslationDto->getTargetLanguageMethods() as $targetLanguageMethodDto ) {
      $method = $this->mapTargetLanguageMethod( $targetLanguageMethodDto );
      if ( ! $method instanceof DuplicateMethod ) {
        $translationMethods[] = new TargetLanguage(
          $targetLanguageMethodDto->getTargetLanguageCode(),
          $method,
          $elements
        );
      }
    }

    if ( ! count( $translationMethods ) ) {
      return [ null, [] ];
    }

    $extraInfo = $sendToTranslationDto->getExtraInformation();

    $translationBatch = new TranslationBatch(
      $sendToTranslationDto->getBatchName(),
      $sendToTranslationDto->getSourceLanguageCode(),
      $translationMethods,
      $extraInfo ?
        $extraInfo->getHowToHandleExistingTranslations() :
        HowToHandleExistingTranslationType::HANDLE_EXISTING_LEAVE,
      $extraInfo ? $extraInfo->getTranslationServiceExtraFields() : null,
      $extraInfo ? DateTime::create( $extraInfo->getDeadline() ) : null
    );

    return $this->translationValidator->validate( $translationBatch );
  }


}
