<?php

namespace WPML\Core\Component\Translation\Application\Repository\Command;

class SaveTranslatorNoteCommand {

  /** @var string  */
  private $itemKind;

  /** @var int  */
  private $itemId;

  /**
   * @var string 'post'|'package'
   */
  private $note;


  public function __construct(
    string $itemKind,
    int $itemId,
    string $note
  ) {
    $this->itemKind = $itemKind;
    $this->itemId   = $itemId;
    $this->note     = $note;
  }


  public function getItemId(): int {
    return $this->itemId;
  }


  public function getNote(): string {
    return $this->note;
  }


  public function getItemKind(): string {
    return $this->itemKind;
  }


}
