<?php

namespace WPML\Core\Component\Translation\Domain\TranslationEditor;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorType;

interface EditorInterface {


  /** @return TranslationEditorType::* */
  public function get();


}
