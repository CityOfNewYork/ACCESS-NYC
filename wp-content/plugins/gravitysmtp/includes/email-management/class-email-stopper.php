<?php

namespace Gravity_Forms\Gravity_SMTP\Email_Management;

use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Email_Stopper {

	protected $category;

	protected $key;

	/**
	 * @var Data_Store_Router
	 */
	protected $data;

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $data_save;

	/**
	 * @var Managed_Email[]
	 */
	protected $email_types = array();

	public function __construct( Data_Store_Router $data, Plugin_Opts_Data_Store $data_save ) {
		$this->data      = $data;
		$this->data_save = $data_save;
	}

	public function get_settings_info() {
		$info = array();
		foreach ( $this->get_email_types() as $type => $data ) {
			$category = $data->category();
			if ( ! isset( $info[ $category ] ) ) {
				$info[ $category ] = array(
					'category' => $category,
					'items' => array(),
				);
			}

			$info[ $category ]['items'][] = array(
				'key'         => $data->get_option_key(),
				'value'       => ! $this->is_blocked( $type ),
				'label'       => $data->label(),
				'description' => $data->description(),
			);
		}

		$info = array_values( $info );

		return $info;
	}

	public function add( Managed_Email $managed_email ) {
		$key                       = $managed_email->key();
		$this->email_types[ $key ] = $managed_email;
	}

	public function remove( $key ) {
		unset( $this->email_types[ $key ] );
	}

	protected function is_blocked( $type ) {
		if ( ! isset( $this->get_email_types()[ $type ] ) ) {
			return false;
		}

		$type    = $this->get_email_types()[ $type ];
		$key     = $type->get_option_key();
		$allowed = $this->data->get_plugin_setting( $key, true );

		return ! Booliesh::get( $allowed );
	}

	public function block( $type ) {
		if ( ! isset( $this->get_email_types()[ $type ] ) ) {
			return;
		}

		$type = $this->get_email_types()[ $type ];
		$key  = $type->get_option_key();
		$this->data_save->save( $key, false );
	}

	public function allow( $type ) {
		if ( ! isset( $this->get_email_types()[ $type ] ) ) {
			return;
		}

		$type = $this->get_email_types()[ $type ];
		$key  = $type->get_option_key();
		$this->data_save->save( $key, true );
	}

	public function stop_all() {
		foreach ( $this->email_types as $type ) {
			if ( ! $this->is_blocked( $type->key() ) ) {
				continue;
			}

			$type->trigger_disable_callback();
		}
	}

	public function stop( $type ) {
		if ( ! isset( $this->get_email_types()[ $type ] ) ) {
			return;
		}

		$type = $this->get_email_types()[ $type ];
		$type->trigger_disable_callback();
	}

	protected function get_email_types() {
		/**
		 * Allows third-parties to add custom managed email types to the system.
		 *
		 * @param array $email_types An array of currently-registered email types.
		 *
		 * @return array
		 */
		return apply_filters( 'gravitysmtp_managed_email_types', $this->email_types );
	}
}
