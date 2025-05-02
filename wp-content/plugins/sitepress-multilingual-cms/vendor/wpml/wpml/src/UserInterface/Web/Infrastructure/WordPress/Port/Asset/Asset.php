<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Port\Asset;

use WPML\UserInterface\Web\Core\Port\Asset\AssetInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Style;

class Asset implements AssetInterface {


  public function enqueueScript( Script $script ) {

    wp_enqueue_script(
      $script->id(),
      plugins_url( $script->src()  ?: '', WPML_PUBLIC_DIR ),
      $script->dependencies(),
      WPML_VERSION,
      [
        'in_footer' => true
      ]
    );

    wp_set_script_translations(
      $script->id(),
      'wpml',
      WPML_ROOT_DIR . '/languages/'
    );

    if ( ! empty( $script->scriptData() ) && ! empty( $script->scriptVarName() ) ) {
      /* @phpstan-ignore-next-line */
      $scriptVarName = $script->scriptVarName() ?: '';
      wp_add_inline_script(
        $script->id(),
        'var ' . $scriptVarName . ' = ' . json_encode( $script->scriptData() ) . ';',
        'before'
      );
    }

  }


  /**
   * @param Style $style
   * @return void
   */
  public function enqueueStyle( Style $style ) {
    if ( $style->src() === null ) {
      return;
    }
    wp_enqueue_style(
      $style->id(),
      plugins_url( $style->src()  ?: '', WPML_PUBLIC_DIR ),
      $style->dependencies(),
      WPML_VERSION
    );

  }


}
