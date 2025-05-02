<?php

namespace WPML\Core\Component\Translation\Domain\Links;

class Item {

  /** @var int */
  private $id;

  /** @var string */
  private $type;

  /** @var string */
  private $content;

  /** @var string */
  private $excerpt;

  /** @var ?string */
  private $languageCode;

  /** @var ?int */
  private $idOriginal;

  /** @var bool */
  private $isDeleted = false;

  /** @var bool */
  private $isPublished = false;

  /** @var bool */
  private $isPublishable = false;

  /** @var bool */
  private $gotPublished = false;

  /** @var bool */
  private $linkHasChanged = false;

  /** @var ?string */
  private $nameBefore;


  public function __construct(
    int $id,
    string $type,
    string $content = '',
    string $excerpt = '',
    string $languageCode = null,
    int $id_original = null
  ) {
    $this->id           = $id;
    $this->type         = $type;
    $this->content      = $content;
    $this->excerpt      = $excerpt;
    $this->idOriginal   = $id_original;
    $this->languageCode = $languageCode;
  }


  public function getId(): int {
    return $this->id;
  }


  /** @return string */
  public function getType() {
    return $this->type;
  }


  /** @return void */
  public function setContent( string $content ) {
    $this->content = $content;
  }


  /** @return void */
  public function setExcerpt( string $excerpt ) {
    $this->excerpt = $excerpt;
  }


  /** @return ?string */
  public function getContent() {
    return $this->content;
  }


  /** @return ?string */
  public function getExcerpt() {
    return $this->excerpt;
  }


  /** @return void */
  public function setLanguageCode( string $languageCode ) {
    $this->languageCode = $languageCode;
  }


  /** @return ?string */
  public function getLanguageCode() {
    return $this->languageCode;
  }


  /** @return void */
  public function setIdOriginal( int $idOriginal ) {
    $this->idOriginal = $idOriginal;
  }


  /** @return ?int */
  public function getIdOriginal() {
    return $this->idOriginal;
  }


  public function isOriginal(): bool {
    return $this->idOriginal === null;
  }


  /** @return void */
  public function markAsDeleted() {
    $this->isDeleted = true;
  }


  public function isDeleted(): bool {
    return $this->isDeleted;
  }


  /** @return void */
  public function markLinkAsChanged( string $nameBefore = null ) {
    $this->linkHasChanged = true;
    $this->nameBefore = $nameBefore;
  }


  public function linkHasChanged(): bool {
    return $this->linkHasChanged;
  }


  /** @return ?string */
  public function getNameBefore() {
    return $this->nameBefore;
  }


  public function canLinkToOtherItems(): bool {
    return trim( $this->content ) !== '' || trim( $this->excerpt ) !== '';
  }


  /** @return void */
  public function markAsPublished() {
    $this->isPublished = true;
  }


  public function isPublished(): bool {
    return $this->isPublished;
  }


  /** @return void */
  public function markAsPublishable() {
    $this->isPublishable = true;
  }


  public function isPublishable(): bool {
    return $this->isPublished || $this->isPublishable;
  }


  /** @return void */
  public function markAsGotPublished() {
    $this->gotPublished = true;
  }


  public function gotPublished(): bool {
    return $this->gotPublished;
  }


}
