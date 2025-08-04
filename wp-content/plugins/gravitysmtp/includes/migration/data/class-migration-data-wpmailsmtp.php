<?php

namespace Gravity_Forms\Gravity_SMTP\Migration\Data;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Migration\Migrator;
use Gravity_Forms\Gravity_SMTP\Migration\Migrator_Collection;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Migration_Data_Wpmailsmtp {

	protected $names_map = array(
		'sendinblue' => 'brevo',
		'amazonses'  => 'amazon-ses',
		'gmail'      => 'google',
		'outlook'    => 'microsoft',
		'smtp'       => 'generic',
	);

	protected $wp_smtp_pro_settings_map = array(
		"sendinblue" => array(
			"api_key" => "",
		),
		"amazonses"  => array(
			"client_id"     => "",
			"client_secret" => ""
		),
		"gmail"      => array(
			"client_id"     => "",
			"client_secret" => "",
		),
		"mailgun"    => array(
			"api_key" => "",
			"domain"  => "",
			"region"  => "__transform_strtolower",
		),
		"outlook"    => array(
			"client_id"     => "",
			"client_secret" => "",
		),
		"postmark"   => array(
			"server_api_token" => "",
		),
		"sendgrid"   => array(
			"api_key" => "",
		),
		"sparkpost"  => array(
			"api_key" => "",
			"region"  => ""
		),
		"zoho"       => array(
			"domain"        => "",
			"client_id"     => "",
			"client_secret" => ""
		),
		'smtp'       => array(
			'host'       => "",
			'port'       => "",
			'encryption' => "__transform_strtolower||encryption_type",
			'autotls'    => "__transform_booleish||auto_tls",
			'auth'       => "__transform_booleish",
			'user'       => "username",
		),
		'elasticemail' => array(
			'api_key' => '',
		),
		'smtp2go' => array(
			'api_key' => '',
		),
	);

	public function get_migrations() {
		/**
		 * @var Migrator_Collection $collection
		 */
		$collection = new Migrator_Collection();

		$wp_smtp_pro_migrator = new Migrator();
		$wp_smtp_pro_options  = get_option( 'wp_mail_smtp' );

		foreach ( $this->wp_smtp_pro_settings_map as $connector => $values ) {
			$og_connector = $connector;
			if ( isset( $this->names_map[ $connector ] ) ) {
				$connector = $this->names_map[ $connector ];
			}

			foreach ( $values as $og_id => $new_id ) {
				$transform = false;

				if ( strpos( $new_id, '__transform_' ) !== false ) {
					$parts     = explode( '||', $new_id );
					$transform = str_replace( '__transform_', '', $parts[0] );
					$new_id    = isset( $parts[1] ) ? $parts[1] : '';
				}

				if ( $transform === 'booleish' ) {
					$transform = array( Booliesh::class, 'get' );
				}

				if ( empty( $new_id ) ) {
					$new_id = $og_id;
				}

				$og_callback = function () use ( $wp_smtp_pro_options, $og_id, $connector, $og_connector, $transform ) {
					$value = $wp_smtp_pro_options[ $og_connector ][ $og_id ];

					if ( $transform ) {
						$value = call_user_func( $transform, $value );
					}

					return $value;
				};

				$wp_smtp_pro_migrator->add_connector_migration( $connector, $og_callback, $this->default_connector_action( $new_id, $connector ) );
			}
		}

		$plugin_to_connector_map = array(
			'from_email'       => Connector_Base::SETTING_FROM_EMAIL,
			'from_name'        => Connector_Base::SETTING_FROM_NAME,
			'from_email_force' => Connector_Base::SETTING_FORCE_FROM_EMAIL,
			'from_name_force'  => Connector_Base::SETTING_FORCE_FROM_NAME,
		);

		$connector_name_map = Gravity_SMTP::container()->get( Connector_Service_Provider::NAME_MAP );

		if ( empty( $connector_name_map ) ) {
			$collection->add( 'wpmailsmtp', $wp_smtp_pro_migrator );

			return $collection;
		}

		foreach ( $plugin_to_connector_map as $og_key => $new_key ) {
			$og_callback = function () use ( $wp_smtp_pro_options, $og_key ) {
				return $wp_smtp_pro_options['mail'][ $og_key ];
			};

			foreach ( $connector_name_map as $connector => $label ) {
				$wp_smtp_pro_migrator->add_connector_migration( $connector, $og_callback, $this->default_connector_action( $new_key, $connector ) );
			}
		}

		$collection->add( 'wpmailsmtp', $wp_smtp_pro_migrator );

		return $collection;
	}

	private function default_connector_action( $new_option_name, $connector ) {
		return function ( $new_value ) use ( $new_option_name, $connector ) {
			/**
			 * @var Opts_Data_Store $data
			 */
			$data = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_OPTS );
			$data->save( $new_option_name, $new_value, $connector );
		};
	}

}