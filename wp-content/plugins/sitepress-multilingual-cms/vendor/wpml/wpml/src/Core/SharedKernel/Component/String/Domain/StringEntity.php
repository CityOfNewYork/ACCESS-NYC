<?php

// phpcs:ignore PHPCompatibility.Keywords.ForbiddenNamesAsDeclared.stringFound
namespace WPML\Core\SharedKernel\Component\String\Domain;

class StringEntity {

  /** @var int */
  private $id;

  /** @var string */
  private $language;

  /** @var string */
  private $context;

  /** @var string */
  private $name;

  /** @var string */
  private $value;

  /** @var int */
  private $status;

  /** @var int */
  private $wordCount;


  public function __construct(
    int $id,
    string $language,
    string $context,
    string $name,
    string $value,
    int $status,
    int $wordCount
  ) {
    $this->id        = $id;
    $this->language  = $language;
    $this->context   = $context;
    $this->name      = $name;
    $this->value     = $value;
    $this->status    = $status;
    $this->wordCount = $wordCount;
  }


  public function getId(): int {
    return $this->id;
  }


  public function getLanguage(): string {
    return $this->language;
  }


  public function getContext(): string {
    return $this->context;
  }


  public function getName(): string {
    return $this->name;
  }


  public function getValue(): string {
    return $this->value;
  }


  public function getStatus(): int {
    return $this->status;
  }


  public function getWordCount(): int {
    return $this->wordCount;
  }


}
