<?php

namespace WPML\Core\Component\Translation\Domain\Settings;

/**
 * This class is representing an obsolete option that we're not using anymore in WPML.
 * Basically we only need it to check if we had automatic translation disabled for any post type.
 *
 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-2143
 */
class TranslateAutomaticallyPerPostType {

  const AUTOMATIC_CONFIG = 'automatic-config';
  const OVERRIDE = 'automatic-override';

  /**
   * @var array<array<string, bool>>
   */
  private $automaticTranslationPerPostTypeConfig;


  /**
   * @param array<string, array<string, bool>> $automaticTranslationPerPostTypeConfig
   */
  public function __construct( array $automaticTranslationPerPostTypeConfig ) {
    $this->automaticTranslationPerPostTypeConfig = $automaticTranslationPerPostTypeConfig;
  }


  /**
   * @return array<string, bool>
   */
  private function postTypesDisabledForAutomaticTranslation(): array {
    $fromConfig = $this->automaticTranslationPerPostTypeConfig[ self::AUTOMATIC_CONFIG ] ?? [];
    $override   = $this->automaticTranslationPerPostTypeConfig[ self::OVERRIDE ] ?? [];

    return array_filter(
      array_merge( $fromConfig, $override ),
      function ( $config ) {
        return $config === false;
      }
    );
  }


  public function hasAnyAutomaticTranslationDisabled(): bool {
    return count( $this->postTypesDisabledForAutomaticTranslation() ) > 0;
  }


}
