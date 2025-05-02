<?php

namespace WPML\Core\SharedKernel\Component\User\Application\Query\Dto;

class UserDto {

  /** @var int */
  private $id;

  /** @var string */
  private $displayName;

  /** @var string */
  private $email;


  public function __construct( int $id, string $displayName, string $email ) {
    $this->id          = $id;
    $this->displayName = $displayName;
    $this->email       = $email;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getDisplayName(): string {
    return $this->displayName;
  }


  public function getEmail(): string {
    return $this->email;
  }


  /**
   * @return array{
   *   id: int,
   *   displayName: string,
   *   email: string
   * }
   */
  public function toArray(): array {
    return [
      'id'          => $this->getId(),
      'displayName' => $this->getDisplayName(),
      'email'       => $this->getEmail(),
    ];
  }


}
