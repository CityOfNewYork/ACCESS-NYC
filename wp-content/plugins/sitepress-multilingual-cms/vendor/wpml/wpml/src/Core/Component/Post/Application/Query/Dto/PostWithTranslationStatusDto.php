<?php

namespace WPML\Core\Component\Post\Application\Query\Dto;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @implements ConstructableFromArrayInterface<PostWithTranslationStatusDto>
 */
final class PostWithTranslationStatusDto
  implements ConstructableFromArrayInterface {
  /** @use ConstructableFromArrayTrait<PostWithTranslationStatusDto> */
  use ConstructableFromArrayTrait;

  /** @var int */
  private $id;

  /** @var string */
  private $title;

  /** @var string */
  private $status;

  /** @var string */
  private $createdAt;

  /** @var string */
  private $postType;

  /** @var array<string, TranslationStatusDto> */
  private $translationStatuses;

  /** @var int|null */
  private $wordCount;

  /** @var string|null */
  private $translatorNote;


  /**
   * @param int                                 $id
   * @param string                              $title
   * @param string                              $status
   * @param string                              $createdAt
   * @param array<string, TranslationStatusDto|array<string, mixed>> $translationStatuses
   * @param int|null                            $wordCount
   * @param string|null                         $translatorNote
   */
  public function __construct(
    int $id,
    string $title,
    string $status,
    string $createdAt,
    string $postType,
    array $translationStatuses,
    int $wordCount = null,
    string $translatorNote = null
  ) {
    $translationStatuses = array_map(
      function ( $translationStatus ) {
        try {
          return is_array( $translationStatus ) ?
            TranslationStatusDto::fromArray( $translationStatus ) :
            $translationStatus;
        } catch ( InvalidArgumentException $e ) {
          return null;
        }
      },
      $translationStatuses
    );

    $translationStatuses = array_filter(
      $translationStatuses,
      function ( $translationStatus ) {
        return $translationStatus instanceof TranslationStatusDto;
      }
    );

    $this->id                  = $id;
    $this->title               = $title;
    $this->status              = $status;
    $this->createdAt           = $createdAt;
    $this->postType            = $postType;
    $this->translationStatuses = $translationStatuses;
    $this->wordCount           = $wordCount;
    $this->translatorNote      = $translatorNote;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getTitle(): string {
    return $this->title;
  }


  public function getStatus(): string {
    return $this->status;
  }


  public function getCreatedAt(): string {
    return $this->createdAt;
  }


  public function getPostType(): string {
    return $this->postType;
  }


  /** @return array<string, TranslationStatusDto> */
  public function getTranslationStatuses(): array {
    return $this->translationStatuses;
  }


  /**
   * @return int|null
   */
  public function getWordCount() {
    return $this->wordCount;
  }


  /**
   * @return string|null
   */
  public function getTranslatorNote() {
    return $this->translatorNote;
  }


}
