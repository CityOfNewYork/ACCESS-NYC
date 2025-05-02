<?php

namespace WPML\Core\Component\Post\Application\Query\Dto;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<PublicationStatusDto>
 */
final class PublicationStatusDto implements ConstructableFromArrayInterface {
  /** @use ConstructableFromArrayTrait<PublicationStatusDto> */
  use ConstructableFromArrayTrait;

  /** @var string */
  private $id;

  /** @var string */
  private $label;


  public function __construct( string $id, string $label ) {
    $this->id = $id;
    $this->label = $label;
  }


  public function getId(): string {
    return $this->id;
  }


  public function getLabel(): string {
    return $this->label;
  }


  /**
   * @return array{ id: string, label: string }
   */
  public function toArray() {
    return [
      'id' => $this->id,
      'label' => $this->label,
    ];
  }


}
