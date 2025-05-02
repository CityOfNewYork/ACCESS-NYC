<?php

namespace WPML\Core\SharedKernel\Component\Item\Application\Query\Dto;

class UntranslatedTypeCountDto {

  /** @var string */
  private $namePlural;

  /** @var string */
  private $nameSingular;

  /** @var int */
  private $count;


  public function __construct( string $namePlural, string $nameSingular, int $count ) {
    $this->namePlural   = $namePlural;
    $this->nameSingular = $nameSingular;
    $this->count        = $count;
  }


  public function getNamePlural(): string {
    return $this->namePlural;
  }


  public function getNameSingular(): string {
    return $this->nameSingular;
  }


  public function getCount(): int {
    return $this->count;
  }


  /**
   * @return array{namePlural: string, nameSingular: string, count: int}
   */
  public function toArray(): array {
    return [
      'namePlural'   => $this->namePlural,
      'nameSingular' => $this->nameSingular,
      'count'        => $this->count,
    ];
  }


}
