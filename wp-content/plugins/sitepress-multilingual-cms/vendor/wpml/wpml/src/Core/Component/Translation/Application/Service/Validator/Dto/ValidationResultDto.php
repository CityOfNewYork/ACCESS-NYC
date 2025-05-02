<?php

namespace WPML\Core\Component\Translation\Application\Service\Validator\Dto;

class ValidationResultDto {

  /** @var string */
  private $type;

  /** @var bool */
  private $valid;


  public function __construct( string $type, bool $valid ) {
    $this->type  = $type;
    $this->valid = $valid;
  }


  /**
   * @return array{
   *   type: string,
   *   valid: bool
   * }
   */
  public function toArray(): array {
    return [
      'type'  => $this->type,
      'valid' => $this->valid,
    ];
  }


}
