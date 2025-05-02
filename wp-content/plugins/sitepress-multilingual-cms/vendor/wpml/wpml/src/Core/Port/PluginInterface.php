<?php

namespace WPML\Core\Port;

interface PluginInterface {


  /** @return string */
  public function getVersion();


  /** @return string */
  public function getVersionWithoutSuffix();


  /** @return string */
  public function getVersionWhenSetupRan();


  /** @return string */
  public function getVersionWhenSetupRanWithoutSuffix();


  /** @return bool */
  public function isSetupComplete();


}
