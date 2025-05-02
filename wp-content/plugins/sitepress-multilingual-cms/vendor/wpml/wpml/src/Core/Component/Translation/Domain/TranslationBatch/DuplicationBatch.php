<?php

namespace WPML\Core\Component\Translation\Domain\TranslationBatch;

class DuplicationBatch {

  /** @var string */
  private $batchName;

  /** @var string */
  private $sourceLanguageCode;

  /** @var string[] */
  private $targetLanguages = [];

  /** @var int[] */
  private $postIds = [];


  /**
   * @param string $batchName
   * @param string $sourceLanguageCode
   * @param string[] $targetLanguages
   * @param int[] $postIds
   */
  public function __construct(
    string $batchName,
    string $sourceLanguageCode,
    array $targetLanguages,
    array $postIds
  ) {
    $this->batchName          = $batchName;
    $this->sourceLanguageCode = $sourceLanguageCode;
    $this->targetLanguages    = $targetLanguages;
    $this->postIds            = $postIds;
  }


  public function getBatchName(): string {
    return $this->batchName;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


  /**
   * @return string[]
   */
  public function getTargetLanguages(): array {
    return $this->targetLanguages;
  }


  /**
   * @return int[]
   */
  public function getPostIds(): array {
    return $this->postIds;
  }


}
