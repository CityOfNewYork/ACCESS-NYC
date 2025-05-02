<?php

namespace WPML\Core\Component\Post\Application\Query\Dto;

class HierarchicalPostDto {

  /** @var int */
  private $id;

  /** @var string */
  private $title;

  /** @var int */
  private $parentId;


  public function __construct( int $id, string $title, int $parentId ) {
    $this->id       = $id;
    $this->title    = $title;
    $this->parentId = $parentId;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getTitle(): string {
    return $this->title;
  }


  public function getParentId(): int {
    return $this->parentId;
  }


}
