<?php

namespace WPML\UserInterface\Web\Core\Component\Notices\WarningTranslationEdit\Application;

use WPML\Core\Port\Event\EventListenerInterface;
use WPML\UserInterface\Web\Core\Port\Asset\AssetInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Style;

class WarningTranslationEditController implements EventListenerInterface {
  const SCRIPT_HANDLE = 'wpml_warning_translation_edit';

  /** @var TranslationEditorInterface */
  private $translationEditor;

  /** @var AssetInterface $asset */
  private $asset;


  public function __construct(
    TranslationEditorInterface $translationEditor,
    AssetInterface $asset
  ) {
    $this->translationEditor = $translationEditor;
    $this->asset = $asset;
  }


  /**
   * @param int          $postId
   * @param string       $pageBuilderName
   *
   * @return void
   */
  public function maybeShowPageBuilderWarning( $postId, $pageBuilderName ) {
    $translationEditorUrl = $this->translationEditor->getTranslationEditorLink( $postId );
    if ( $translationEditorUrl ) {
        $this->enqueueAssets( $pageBuilderName, $translationEditorUrl );
    }
  }


  /**
   * @param string $pageBuilderName
   * @param string $translationEditorUrl
   * @return void
   */
  private function enqueueAssets( $pageBuilderName, $translationEditorUrl ) {

    $script_m = new Script( 'wpml-modules' );
    $script_m->setSrc( 'public/js/node-modules.js' );
    $this->asset->enqueueScript( $script_m );

    $script = new Script( self::SCRIPT_HANDLE );
    $script->setSrc( 'public/js/notice-warning-translation-edit.js' )
        ->setScriptData(
          array(
              'page_builder_name' => $pageBuilderName,
              'translation_editor_url' => $translationEditorUrl
            )
        )
        ->setScriptVarName( self::SCRIPT_HANDLE )
        ->setDependencies( ['wpml-modules', 'wp-i18n'] );

    $style = new Style( self::SCRIPT_HANDLE.'_css' );
    $style->setSrc( 'public/css/notice-warning-translation-edit.css' );

    $this->asset->enqueueScript( $script );
    $this->asset->enqueueStyle( $style );
  }


}
