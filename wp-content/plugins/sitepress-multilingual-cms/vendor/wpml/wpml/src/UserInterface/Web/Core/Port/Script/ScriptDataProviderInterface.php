<?php

namespace WPML\UserInterface\Web\Core\Port\Script;

interface ScriptDataProviderInterface {


  /**
    * The script data is provided as global in the js "window" object.
    * This returns the key name where the script data is stored.
    */
  public function jsWindowKey(): string;


  /**
   * @return array<string, mixed>
   */
  public function initialScriptData(): array;


}
