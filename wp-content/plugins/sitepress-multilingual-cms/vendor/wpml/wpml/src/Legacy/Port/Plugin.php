<?php

namespace WPML\Legacy\Port;

use WPML\Core\Port\PluginInterface;

class Plugin implements PluginInterface {

  /** @var \SitePress $sitepress */
  private $sitepress;


  public function __construct( \SitePress $sitepress ) {
    $this->sitepress = $sitepress;
  }


  public function getVersion() {
    return defined( 'ICL_SITEPRESS_VERSION' )
      ? ICL_SITEPRESS_VERSION
      : WPML_VERSION;
  }


  public function getVersionWithoutSuffix() {
    return $this->versionWithoutSuffix( $this->getVersion() );
  }


  public function getVersionWhenSetupRan() {
    $version = get_option( 'wpml_start_version' );
    return is_string( $version ) ? $version : '0.0.0';
  }


  public function getVersionWhenSetupRanWithoutSuffix() {
    return $this->versionWithoutSuffix( $this->getVersionWhenSetupRan() );
  }


  public function isSetupComplete() {
    return $this->sitepress->is_setup_complete();
  }


  /**
   * @param string $version
   * @return string
   */
  private function versionWithoutSuffix( $version ) {
    $versionWithoutSuffix = preg_replace( '/[-+].*$/', '', $version );
    return $versionWithoutSuffix ?? '';
  }


}
