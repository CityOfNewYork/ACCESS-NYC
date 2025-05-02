<?php

namespace WPML\Core\Component\Translation\Application\Service\Dto;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @implements ConstructableFromArrayInterface<SendToTranslationDto>
 *
 * @phpstan-import-type SendToTranslationExtraInformationArray from SendToTranslationExtraInformationDto
 *
 * @phpstan-type SendToTranslationDtoArray array{
 *    targetLanguageMethods?: array{targetLanguageCode: string, translationMethod: string, translatorId: int|null}[]|null,
 *    batchName?: string|null,
 *    sourceLanguageCode?: string|null,
 *    posts?: array<int>|null,
 *    stringPackages?: array<int>|null,
 *    strings?: array<int>|null,
 *    extraInformation?: SendToTranslationExtraInformationArray|null
 * }
 */
class SendToTranslationDto implements ConstructableFromArrayInterface {

  /** @var string */
  private $batchName;

  /** @var string */
  private $sourceLanguageCode;

  /**
   * @var TargetLanguageMethodDto[]
   */
  private $targetLanguageMethods;

  /**
   * @var int[]
   */
  private $posts = [];

  /**
   * @var int[]
   */
  private $packages = [];

  /**
   * @var int[]
   */
  private $strings = [];

  /** @var SendToTranslationExtraInformationDto|null */
  private $extraInformation;


  /**
   * @param string $batchName
   * @param string $sourceLanguageCode
   * @param TargetLanguageMethodDto[] $targetLanguageMethods
   * @param int[] $posts
   * @param int[] $packages
   * @param int[] $strings
   * @param SendToTranslationExtraInformationDto | null $extraInformation
   */
  public function __construct(
    string $batchName,
    string $sourceLanguageCode,
    array $targetLanguageMethods,
    array $posts,
    array $packages,
    array $strings,
    SendToTranslationExtraInformationDto $extraInformation = null
  ) {
    $this->batchName             = $batchName;
    $this->sourceLanguageCode    = $sourceLanguageCode;
    $this->targetLanguageMethods = $targetLanguageMethods;
    $this->posts                 = $posts;
    $this->packages              = $packages;
    $this->strings               = $strings;
    $this->extraInformation      = $extraInformation;
  }


  public function getBatchName(): string {
    return $this->batchName;
  }


  public function getSourceLanguageCode(): string {
    return $this->sourceLanguageCode;
  }


  /**
   * @return TargetLanguageMethodDto[]
   */
  public function getTargetLanguageMethods(): array {
    return $this->targetLanguageMethods;
  }


  /**
   * @return SendToTranslationExtraInformationDto|null
   */
  public function getExtraInformation() {
    return $this->extraInformation;
  }


  /**
   * @return int[]
   */
  public function getPosts(): array {
    return $this->posts;
  }


  /**
   * @return int[]
   */
  public function getPackages(): array {
    return $this->packages;
  }


  /**
   * @return int[]
   */
  public function getStrings(): array {
    return $this->strings;
  }


  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh


  /**
   * @phpstan-param SendToTranslationDtoArray $array
   *
   * @return SendToTranslationDto
   * @throws InvalidArgumentException
   * @throws Exception
   * @psalm-suppress MoreSpecificImplementedParamType
   */
  public static function fromArray( $array ): SendToTranslationDto {
    if ( ! isset( $array['targetLanguageMethods'] ) ) {
      throw new InvalidArgumentException( 'Language pairs cannot be empty' );
    }

    $targetLanguageMethods = [];
    foreach ( $array['targetLanguageMethods'] as $targetLanguageMethod ) {
      $targetLanguageMethods[] = TargetLanguageMethodDto::fromArray( $targetLanguageMethod );
    }

    if ( ! isset( $array['batchName'] ) ) {
      throw new InvalidArgumentException( 'Batch name cannot be empty' );
    }

    if ( ! isset( $array['sourceLanguageCode'] ) ) {
      throw new InvalidArgumentException( 'Source language code cannot be empty' );
    }

    if ( ! isset( $array['posts'] )
         && ! isset( $array['stringPackages'] )
         && ! isset( $array['strings'] ) ) {
      throw new InvalidArgumentException(
        'Posts, packages and strings cannot be empty. 
        At least one of those elements must be specified.'
      );
    }

    $extraInformation = $array['extraInformation'] ?? [];

    return new self(
      $array['batchName'],
      $array['sourceLanguageCode'],
      $targetLanguageMethods,
      $array['posts'] ?? [],
      $array['stringPackages'] ?? [],
      $array['strings'] ?? [],
      SendToTranslationExtraInformationDto::fromArray( $extraInformation )
    );
  }


  // phpcs:enable Generic.Metrics.CyclomaticComplexity.TooHigh

}
