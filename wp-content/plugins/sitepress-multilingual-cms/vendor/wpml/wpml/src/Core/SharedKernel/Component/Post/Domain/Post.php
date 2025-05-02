<?php

namespace WPML\Core\SharedKernel\Component\Post\Domain;

class Post {

  /** @var int */
  private $id;

  /** @var string */
  private $status;

  /** @var string */
  private $type;

  /** @var string */
  private $title;

  /** @var string */
  private $content;

  /** @var string */
  private $excerpt;


  public function __construct(
    int $id,
    string $status,
    string $type,
    string $title,
    string $content,
    string $excerpt
  ) {
    $this->id      = $id;
    $this->status  = $status;
    $this->type    = $type;
    $this->title   = $title;
    $this->content = $content;
    $this->excerpt = $excerpt;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getStatus(): string {
    return $this->status;
  }


  public function getType(): string {
    return $this->type;
  }


  public function getTitle(): string {
    return $this->title;
  }


  public function getContent(): string {
    return $this->content;
  }


  public function getExcerpt(): string {
    return $this->excerpt;
  }


}
