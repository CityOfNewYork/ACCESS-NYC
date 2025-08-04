<?php

namespace Gravity_Forms\Gravity_Tools\Data;

interface Oauth_Data_Handler {

	public function get( $key, $namespace = 'config' );

	public function save( $key, $value, $namespace = 'config' );

}