<?php

namespace WPML\Legacy\Component\Translation\Domain\TranslationBatch\Validator;

use WPML\Core\Component\Translation\Domain\TranslationBatch\Element;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TargetLanguage;
use WPML\Core\Component\Translation\Domain\TranslationBatch\TranslationBatch;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\IgnoredElement;
use WPML\Core\Component\Translation\Domain\TranslationBatch\Validator\ValidatorInterface;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\TM\TranslationDashboard\EncodedFieldsValidation\Validator;
use function WPML\Container\make;

class Base64Validator implements ValidatorInterface {

  const IGNORED_ELEMENT_REASON = 'base64_encoded';

  /** @var Validator */
  private $base64Validator;

  /** @var array<string, int[]> */
  private $alreadyIgnoredElementIds = [
    TranslationType::POST    => [],
    TranslationType::PACKAGE => [],
  ];


  public function __construct() {
    $this->base64Validator = make( Validator::class );
  }


  /**
   * @param TranslationBatch $translationBatch
   *
   * @return array{0: TranslationBatch, 1: IgnoredElement[]}
   */
  public function validate( TranslationBatch $translationBatch ): array {
    $ignoredElements = [];
    $targetLanguages = [];

    list( $postIds, $packageIds ) = $this->extractPostAndPackageIds( $translationBatch );

    $invalidPostAndPackageIds = $this->base64Validator->getInvalidPostAndPackageIds(
      $postIds,
      $packageIds
    );

    if ( ! is_array( $invalidPostAndPackageIds ) || count( $invalidPostAndPackageIds ) !== 2 ) {
      return [ $translationBatch, $ignoredElements ];
    }

    list( $invalidPostIds, $invalidPackageIds ) = $invalidPostAndPackageIds;
    if ( empty( $invalidPostIds ) && empty( $invalidPackageIds ) ) {
      return [ $translationBatch, $ignoredElements ];
    }

    foreach ( $translationBatch->getTargetLanguages() as $targetLanguage ) {
      list( $postElements, $packageElements, $otherElements ) = $this->separateElements( $targetLanguage );

      // Initially set the validElements value to be all elements that are not posts or packages
      $validElements = $otherElements;

      // If the $invalidPostIds is empty, just add the post elements to $validElements immediately
      if ( empty( $invalidPostIds ) ) {
        $validElements = array_merge( $validElements, $postElements );
      } else { // If we have invalid posts, we need to separate the valid and invalid post elements
        list( $ignoredPosts, $validPosts ) = $this->validateElementsOfType(
          TranslationType::POST,
          $invalidPostIds,
          $postElements,
          $targetLanguage
        );

        $ignoredElements = array_merge( $ignoredElements, $ignoredPosts );
        $validElements   = array_merge( $validElements, $validPosts );
      }

      // If the $invalidPackageIds is empty, just add the package elements to $validElements immediately
      if ( empty( $invalidPackageIds ) ) {
        $validElements = array_merge( $validElements, $packageElements );
      } else { // If we have invalid packages, we need to separate the valid and invalid package elements
        list( $ignoredPackages, $validPackages ) = $this->validateElementsOfType(
          TranslationType::PACKAGE,
          $invalidPackageIds,
          $packageElements,
          $targetLanguage
        );

        $ignoredElements = array_merge( $ignoredElements, $ignoredPackages );
        $validElements   = array_merge( $validElements, $validPackages );
      }

      if ( ! empty( $validElements ) ) {
        $targetLanguages[] = new TargetLanguage(
          $targetLanguage->getLanguageCode(),
          $targetLanguage->getMethod(),
          $validElements
        );
      }
    }

    $translationBatch = $translationBatch->copyWithNewTargetLanguages( $targetLanguages );

    return [ $translationBatch, $ignoredElements ];
  }


  /**
   * @param string $type
   * @param int[] $invalidIds
   * @param Element[] $elements
   * @param TargetLanguage $targetLanguage
   *
   * @return array{0: IgnoredElement[], 1: Element[]}
   */
  private function validateElementsOfType(
    string $type,
    array $invalidIds,
    array $elements,
    TargetLanguage $targetLanguage
  ): array {
    $ignoredElements = [];
    $validElements   = [];

    foreach ( $elements as $element ) {
      $elementType = $element->getType();
      $elementId   = $element->getElementId();

      $isInvalid = $elementType->get() === $type && in_array( $elementId, $invalidIds );

      if ( $isInvalid ) {
        if ( ! in_array( $elementId, $this->alreadyIgnoredElementIds[ $elementType->get() ] ) ) {
          $ignoredElements[] = new IgnoredElement(
            $elementType,
            $elementId,
            $targetLanguage->getLanguageCode(),
            $targetLanguage->getMethod(),
            self::IGNORED_ELEMENT_REASON
          );

          $this->alreadyIgnoredElementIds[ $elementType->get() ][] = $elementId;
        }
      } else {
        $validElements[] = $element;
      }
    }

    return [ $ignoredElements, $validElements ];
  }


  /**
   * @param TargetLanguage $targetLanguage
   *
   * @return Element[][]
   */
  private function separateElements( TargetLanguage $targetLanguage ): array {
    $postElements    = [];
    $packageElements = [];
    $otherElements   = [];

    foreach ( $targetLanguage->getElements() as $element ) {
      switch ( $element->getType()->get() ) {
        case TranslationType::POST:
          $postElements[] = $element;
          break;
        case TranslationType::PACKAGE:
          $packageElements[] = $element;
          break;
        default:
          $otherElements[] = $element;
          break;
      }
    }

    return [
      $postElements,
      $packageElements,
      $otherElements
    ];
  }


  /**
   * @param TranslationBatch $translationBatch
   *
   * @return int[][]
   */
  private function extractPostAndPackageIds( TranslationBatch $translationBatch ): array {
    $ids = [
      TranslationType::POST    => [],
      TranslationType::PACKAGE => []
    ];

    foreach ( $translationBatch->getTargetLanguages() as $targetLanguage ) {
      foreach ( $targetLanguage->getElements() as $element ) {
        $elementType = $element->getType()->get();
        if ( $elementType === TranslationType::POST || $elementType === TranslationType::PACKAGE ) {
          $ids[ $elementType ][] = $element->getElementId();
        }
      }
    }

    return [
      $ids[ TranslationType::POST ],
      $ids[ TranslationType::PACKAGE ]
    ];
  }


}
