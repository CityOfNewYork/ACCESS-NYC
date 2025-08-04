<?php

namespace Gravity_Forms\Gravity_SMTP\Data_Store;

interface Data_Store {

	public function get( $setting_name, $connector );

	public function save( $setting_name, $value, $connector );

}