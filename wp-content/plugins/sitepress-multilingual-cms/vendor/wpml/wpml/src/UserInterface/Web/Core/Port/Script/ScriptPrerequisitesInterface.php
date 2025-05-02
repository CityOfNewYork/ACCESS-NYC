<?php

namespace WPML\UserInterface\Web\Core\Port\Script;

interface ScriptPrerequisitesInterface {


  /**
    * Returns if the prequisites are met for the script to be loaded.
    */
  public function scriptPrerequisitesMet(): bool;


}
