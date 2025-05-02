<?php

namespace WPML\Core\Port\Event;

abstract class Event {

  /** @var string */
  private $name;

  /** @var mixed[] */
  private $payload;


  /**
   * @param string $name
   * @param mixed[] $payload
   */
  public function __construct( string $name, array $payload = [] ) {
    $this->name    = $name;
    $this->payload = $payload;
  }


  public function getName(): string {
    return $this->name;
  }


  /**
   * @return mixed[]
   */
  public function getPayload(): array {
    return $this->payload;
  }


}
