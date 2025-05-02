<?php

namespace WPML\Core\Component\Translation\Domain\TranslationEditor;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorType;

class WordpressEditor implements EditorInterface {


  public function get(): string {
    return TranslationEditorType::WORDPRESS;
  }


}
