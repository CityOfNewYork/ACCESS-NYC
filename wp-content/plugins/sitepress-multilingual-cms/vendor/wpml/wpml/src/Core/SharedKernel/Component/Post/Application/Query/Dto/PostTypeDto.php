<?php

namespace WPML\Core\SharedKernel\Component\Post\Application\Query\Dto;

class PostTypeDto {

  /** @var string */
  private $id;

  /** @var string */
  private $title;

  /** @var string */
  private $singular;

  /** @var string */
  private $plural;

  /** @var bool */
  private $isDisplayAsTranslated;

  /** @var bool */
  private $hierarchical;


  public function __construct(
    string $id,
    string $title,
    string $singular,
    string $plural,
    bool $hierarchical,
    bool $isDisplayAsTranslated = false
  ) {
    $this->id                    = $id;
    $this->title                 = $title;
    $this->singular              = $singular;
    $this->plural                = $plural;
    $this->hierarchical          = $hierarchical;
    $this->isDisplayAsTranslated = $isDisplayAsTranslated;
  }


  public function getId(): string {
    return $this->id;
  }


  public function getTitle(): string {
    return $this->title;
  }


  public function getSingular(): string {
    return $this->singular;
  }


  public function getPlural(): string {
    return $this->plural;
  }


  public function isHierarchical(): bool {
    return $this->hierarchical;
  }


  public function isDisplayAsTranslated(): bool {
    return $this->isDisplayAsTranslated;
  }


  /**
   * @return array{
   *   id: string,
   *   title: string,
   *   singular: string,
   *   plural: string,
   *   hierarchical: bool,
   *   isDisplayAsTranslated: bool
   * }
   */
  public function toArray(): array {
    return [
      'id'                    => $this->id,
      'title'                 => $this->title,
      'singular'              => $this->singular,
      'plural'                => $this->plural,
      'hierarchical'          => $this->hierarchical,
      'isDisplayAsTranslated' => $this->isDisplayAsTranslated,
    ];
  }


}
