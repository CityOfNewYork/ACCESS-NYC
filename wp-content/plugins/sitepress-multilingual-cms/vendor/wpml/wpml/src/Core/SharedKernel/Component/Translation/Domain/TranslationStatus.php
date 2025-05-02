<?php

namespace WPML\Core\SharedKernel\Component\Translation\Domain;

class TranslationStatus {

  const NOT_TRANSLATED = 0;
  const WAITING_FOR_TRANSLATOR = 1;
  const IN_PROGRESS = 2;
  /**
   * Virtual state. It has no equivalent in icl_translation_status.status field.
   *
   * Instead of that "needs update" posts have icl_translation_status.status = 10 ( completed ) and
   * icl_translation_status.needs_update = 1
   */
  const NEEDS_UPDATE = 3;
  const READY_TO_DOWNLOAD = 4;
  const DUPLICATE = 9;
  const COMPLETE = 10;
  /**
   * Virtual state. It has no equivalent in icl_translation_status.status field.
   * Instead of that "needs update" posts have icl_translation_status.status = 10 ( completed ) and
   *  icl_translation_status.review_needed = 'NEEDS_REVIEW
   */
  const NEEDS_REVIEW = 30;
  const ATE_NEEDS_RETRY = 40;
  const ATE_CANCELED = 42;

  /** @var int */
  private $value;


  public function __construct( int $value ) {
    if ( in_array( $value, $this->getAll(), true ) ) {
      $this->value = $value;
    } else {
      $this->value = self::NOT_TRANSLATED;
    }
  }


  /**
   * Returns all public statuses.
   *
   * @return int[]
   */
  public function getPublic() {
    return [
      self::NOT_TRANSLATED,
      self::NEEDS_UPDATE,
      self::IN_PROGRESS,
      self::COMPLETE,
    ];
  }


  /**
   * @return int[]
   */
  public function getAll() {
    return [
      self::NOT_TRANSLATED,
      self::WAITING_FOR_TRANSLATOR,
      self::IN_PROGRESS,
      self::NEEDS_UPDATE,
      self::READY_TO_DOWNLOAD,
      self::DUPLICATE,
      self::COMPLETE,
      self::NEEDS_REVIEW,
      self::ATE_NEEDS_RETRY,
      self::ATE_CANCELED
    ];
  }


  public function get(): int {
    return $this->value;
  }


  /**
   * A displayed status depends on multiple fields.
   * We need to take all of them into consideration to derive the final displayed status.
   *
   * @param array{
   *   status: int|string,
   *   needs_update: bool|int,
   *   review_status: string|null,
   *   element_id: int|string
   * } $data
   *
   * @return self
   */
  public static function getPostDisplayStatus( array $data ): self {
    $statusValue = (int) $data['status'];
    if ( $statusValue === TranslationStatus::ATE_CANCELED ) {
      $statusValue = TranslationStatus::NOT_TRANSLATED;
    }

    /**
     * This is the case when either a post was translated using the Connect translations feature
     * or it was translated once, then sent to translation again and the second job got cancelled.
     */
    if ( $statusValue === TranslationStatus::NOT_TRANSLATED && $data['element_id'] ) {
      $statusValue = TranslationStatus::COMPLETE;
    }

    if ( $data['needs_update'] ) {
      $statusValue = TranslationStatus::NEEDS_UPDATE;
    }

    if ( $data['review_status'] === 'NEEDS_REVIEW' ) {
      $statusValue = TranslationStatus::NEEDS_REVIEW;
    }

    return new self( $statusValue );
  }


}
