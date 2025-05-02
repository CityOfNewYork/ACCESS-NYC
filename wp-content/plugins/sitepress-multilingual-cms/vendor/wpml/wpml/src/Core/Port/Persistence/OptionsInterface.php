<?php

namespace WPML\Core\Port\Persistence;

interface OptionsInterface {


  /**
   * @param string $optionName
   * @param mixed  $defaultValue
   *
   * @return mixed
   */
  public function get( string $optionName, $defaultValue = false );


  /**
   * @param string $optionName
   * @param mixed  $value
   * @param bool   $autoload
   *
   * @return void
   */
  public function save( string $optionName, $value, $autoload = false );


  /**
   * @param string $optionName
   *
   * @return void
   */
  public function delete( string $optionName );


}
