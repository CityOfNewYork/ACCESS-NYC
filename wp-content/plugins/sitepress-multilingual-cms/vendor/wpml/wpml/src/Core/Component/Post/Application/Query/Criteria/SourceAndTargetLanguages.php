<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\PHP\Exception\InvalidArgumentException;

final class SourceAndTargetLanguages {

  /** @var string */
  private $sourceLanguageCode;

  /** @var string[] */
  private $targetLanguageCodes;


  /**
   * @param string   $sourceLanguageCode
   * @param string[] $targetLanguageCodes
   *
   * @throws InvalidArgumentException
   */
  public function __construct( string $sourceLanguageCode, array $targetLanguageCodes ) {
    if ( empty( $targetLanguageCodes ) ) {
      throw new InvalidArgumentException( 'Target language codes cannot be empty' );
    }

    if ( in_array( $sourceLanguageCode, $targetLanguageCodes, true ) ) {
      throw new InvalidArgumentException( 'Source language code cannot be present in target language codes' );
    }

    $this->sourceLanguageCode  = $sourceLanguageCode;
    $this->targetLanguageCodes = $targetLanguageCodes;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


  /**
   * @return string[]
   */
  public function getTargetLanguageCodes(): array {
    return $this->targetLanguageCodes;
  }


}
