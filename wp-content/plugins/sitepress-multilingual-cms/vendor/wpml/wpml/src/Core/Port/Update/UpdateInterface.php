<?php

namespace WPML\Core\Port\Update;

interface UpdateInterface {


  /**
   * @return bool Wheter the update was successful or not.
   */
  public function update();


}
