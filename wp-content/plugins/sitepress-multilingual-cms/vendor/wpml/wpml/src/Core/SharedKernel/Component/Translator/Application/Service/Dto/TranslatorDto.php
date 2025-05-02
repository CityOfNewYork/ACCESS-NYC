<?php

namespace WPML\Core\SharedKernel\Component\Translator\Application\Service\Dto;

class TranslatorDto {

  /** @var int */
  private $id;

  /** @var string */
  private $name;

  /** @var string */
  private $userName;

  /** @var LanguagePairDto[] */
  private $languagePairs;


  /**
   * @param int $id
   * @param string $name
   * @param string $userName
   * @param LanguagePairDto[] $languagePairs
   */
  public function __construct(
    int $id,
    string $name,
    string $userName,
    array $languagePairs
  ) {
    $this->id            = $id;
    $this->name          = $name;
    $this->userName      = $userName;
    $this->languagePairs = $languagePairs;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getName(): string {
    return $this->name;
  }


  public function getUserName(): string {
    return $this->userName;
  }


  /**
   * @return LanguagePairDto[]
   */
  public function getLanguagePairs(): array {
    return $this->languagePairs;
  }


  /**
   * @return array{
   *   id: int,
   *   name: string,
   *   userName: string,
   *   languagePairs: array<array{
   *   from: string,
   *   to: string[]
   * }>
   * }
   */
  public function toArray(): array {
    return [
      'id'            => $this->getId(),
      'name'          => $this->getName(),
      'userName'      => $this->getUserName(),
      'languagePairs' => array_map(
        function ( LanguagePairDto $languagePairDto ) {
          return $languagePairDto->toArray();
        },
        $this->getLanguagePairs()
      ),
    ];
  }


}
