<?php

namespace WPML\Core\SharedKernel\Component\Translator\Application\Service\Dto;

class LanguagePairDto {

  /** @var string */
  private $from;

  /** @var string[] */
  private $to;


  /**
   * @param string $from
   * @param string[] $to
   */
  public function __construct ( string $from, array $to ) {
    $this->from = $from;
    $this->to   = $to;
  }


  public function getFrom (): string {
    return $this->from;
  }


  /**
   * @return string[]
   */
  public function getTo (): array {
    return $this->to;
  }


  /**
   * @return array{
   *   from: string,
   *   to: string[]
   * }
   */
  public function toArray (): array {
    return [
      'from' => $this->getFrom(),
      'to'   => $this->getTo(),
    ];
  }


}
