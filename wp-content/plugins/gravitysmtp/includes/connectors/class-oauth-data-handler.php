<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors;

use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_Tools\Data\Oauth_Data_Handler as Oauth_Data_Handler_Base;

class Oauth_Data_Handler implements Oauth_Data_Handler_Base {

	/**
	 * @var Data_Store_Router $data
	 */
	protected $data;

	/**
	 * @var Opts_Data_Store
	 */
	protected $opts_store;

	public function __construct( $data_router, $opts_store ) {
		$this->data       = $data_router;
		$this->opts_store = $opts_store;
	}

	public function get( $key, $connector = 'config' ) {
		return $this->data->get_setting( $connector, $key );
	}

	public function save( $key, $value, $connector = 'config' ) {
		return $this->opts_store->save( $key, $value, $connector );
	}

}