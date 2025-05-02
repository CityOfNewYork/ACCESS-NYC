<?php

namespace GatherContent\Importer\Admin;

use GatherContent\Importer\API;
use GatherContent\Importer\General;
use GatherContent\Importer\Settings\Setting;
use GatherContent\Importer\Settings\Form_Section;

class Admin extends Base {

	public $option_name = General::OPTION_NAME;
	public $option_group = 'gathercontent_importer_settings';
	public $mapping_wizard;

	/**
	 * Default option value (if none is set)
	 *
	 * @var array
	 */
	public $default_options = array(
		'account_email'     => '',
		'platform_url_slug' => '',
		'api_key'           => '',
		'migrated'          => false,
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @param $api API object
	 *
	 * @since 3.0.0
	 *
	 */
	public function __construct( API $api ) {
		global $pagenow;
		parent::set_api( $api );
		parent::__construct();

		if (
			$this->get_setting( 'account_email' )
			&& $this->get_setting( 'platform_url_slug' )
			&& $this->get_setting( 'api_key' )
		) {

			if ( $this->should_migrate() ) {
				$this->settings()->options['migrated'] = true;
				$this->settings()->update();
			}

			$this->step = 1;
			$this->api()->set_user( $this->get_setting( 'account_email' ) );
			$this->api()->set_api_key( $this->get_setting( 'api_key' ) );

			// Get 'me'. If that fails, try again w/o cached response, to flush "fail" response cache.
			if ( ! defined( 'DOING_AJAX' ) && ! $this->api()->get_me() && ! $this->api()->get_me( 1 ) ) {

				if ( 'admin.php' === $pagenow && self::SLUG === $this->_get_val( 'page' ) ) {

					$response = $this->api()->get_last_response();

					$message = __( 'We had trouble connecting to the Content Workflow API. Please check your settings.', 'content-workflow-by-bynder' );

					if ( is_wp_error( $response ) ) {
						$message .= '</p><p>' . sprintf( esc_html__( 'The error received: %s', 'content-workflow-by-bynder' ), $response->get_error_message() );
					}

					$this->add_settings_error( $this->option_name, 'gc-api-connect-fail', $message, 'error' );
				}

				$this->step = 0;
			}
		}

		if (
			! defined( 'DOING_AJAX' )
			&& 'admin.php' === $pagenow && self::SLUG === $this->_get_val( 'page' )
			&& \GatherContent\Importer\auth_enabled()
			&& ! $this->get_setting( 'auth_verified' )
		) {

			$message = __( 'The provided authentication username and/or password is incorrect. If you\'re not sure what this is, please contact your site adminstrator.', 'content-workflow-by-bynder' );

			$this->add_settings_error( $this->option_name, 'gc-http-auth-fail', $message, 'error' );
		}

		if ( $this->step > 0 ) {
			$this->mapping_wizard = new Mapping_Wizard( $this );
		}

	}

	/**
	 * Initiate admin hooks
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function init_hooks() {
		parent::init_hooks();

		if ( $this->mapping_wizard ) {
			$this->mapping_wizard->init_hooks();
		}
	}

	public function sanitize_settings( $settings ) {
		$settings = parent::sanitize_settings( $settings );

		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $value ) {

			switch ( $key ) {
				case 'account_email':
					$value = is_email( $value ) ? sanitize_text_field( $value ) : '';
					break;
				default:
					$value = is_scalar( $value ) ? sanitize_text_field( $value ) : '';
					break;
			}

			$settings[ $key ] = $value;
		}

		if ( isset( $settings['account_owner_email'] ) ) {
			unset( $settings['account_owner_email'] );
		}

		if ( \GatherContent\Importer\auth_enabled() ) {

			$settings['auth_verified'] = false;

			if ( isset( $settings['auth_username'], $settings['auth_pw'] ) ) {

				$result = wp_remote_head(
					admin_url( 'admin-post.php' ),
					array(
						'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
						'headers'   => array(
							'Authorization' => 'Basic ' . base64_encode( $settings['auth_username'] . ':' . $settings['auth_pw'] ),
						),
					)
				);

				$settings['auth_verified'] = isset( $result['response']['code'] ) && 200 === $result['response']['code'];
			}
		}

		return $settings;
	}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function admin_menu() {
		$page = add_menu_page(
			'Content Workflow',
			'Content Workflow',
			\GatherContent\Importer\view_capability(),
			self::SLUG,
			array( $this, 'admin_page' ),
			GATHERCONTENT_URL . 'images/menu-logo.svg'
		);

		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_style' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( GATHERCONTENT_PATH . 'gathercontent-importer.php' ), array(
			$this,
			'settings_link'
		) );

	}

	/**
	 * Add Settings page to plugin action links in the Plugins table.
	 *
	 * @param array $links Default plugin action links.
	 *
	 * @return array $links Amended plugin action links.
	 * @since  3.0.3
	 *
	 */
	public function settings_link( $links ) {

		$links[] = sprintf( '<a href="%s">%s</a>', $this->url, __( 'Settings', 'content-workflow-by-bynder' ) );

		return $links;
	}

	public function admin_page() {
		if ( $this->should_migrate() ) {
			add_action( 'admin_footer', array( $this, 'migrate_settings' ) );
		}

		if ( version_compare( get_option( 'gathercontent_version' ), GATHERCONTENT_VERSION, '<' ) ) {
			update_option( 'gathercontent_version', GATHERCONTENT_VERSION );
		}

		$this->view(
			'admin-page',
			array(
				'logo'              => $this->logo,
				'option_group'      => $this->option_group,
				'settings_sections' => Form_Section::get_sections( self::SLUG ),
			)
		);
	}

	/**
	 * Initializes the plugin's setting, and settings sections/Fields.
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function initialize_settings_sections() {
		if ( $this->step > 0 ) {
			$this->api_setup_complete();
		}

		$this->api_setup_settings();

		parent::initialize_settings_sections();
	}

	public function api_setup_settings() {

		$section = new Form_Section(
			'step_1',
			esc_html__( 'API Credentials', 'content-workflow-by-bynder' ),
			array( $this, 'api_setup_settings_cb' ),
			self::SLUG
		);

		$section->add_field(
			'account_email',
			esc_html__( 'Content Workflow Email Address', 'content-workflow-by-bynder' ),
			array( $this, 'account_email_field_cb' )
		);

		$section->add_field(
			'platform_url_slug',
			esc_html__( 'Platform URL', 'content-workflow-by-bynder' ),
			array( $this, 'platform_url_slug_field_cb' )
		);

		$section->add_field(
			'api_key',
			esc_html__( 'API Key', 'content-workflow-by-bynder' ),
			array( $this, 'api_key_field_cb' )
		);

		if ( \GatherContent\Importer\auth_enabled() ) {
			$section = new Form_Section(
				'auth',
				esc_html__( 'HTTP Authentication Credentials', 'content-workflow-by-bynder' ),
				$this->view( 'auth-enabled-desc', array(), false ),
				self::SLUG
			);

			$section->add_field(
				'auth_username',
				esc_html__( 'Username', 'content-workflow-by-bynder' ),
				array( $this, 'auth_username_field_cb' )
			);

			$section->add_field(
				'auth_pw',
				esc_html__( 'Password', 'content-workflow-by-bynder' ),
				array( $this, 'auth_pw_field_cb' )
			);
		}
	}

	public function api_setup_settings_cb() {
		if ( $key = $this->should_migrate() ) {
			echo '<p><strong>' . esc_html__( 'NOTE:', 'content-workflow-by-bynder' ) . '</strong> ' .
			     esc_html__( 'It looks like you are migrating from a previous version of the GatherContent plugin.', 'content-workflow-by-bynder' ) .
			     '<br>' .
			     esc_html__( 'You will need to set up new GatherContent API credentials to continue. Instructions for getting your API key can be found ', 'content-workflow-by-bynder' ) .
			     '<a href="https://gathercontent.com/developers/authentication/" target="_blank">here</a></p>';

			if ( $slug = get_option( $key . '_api_url' ) ) {
				$this->settings()->options['platform_url_slug'] = $slug;
			}
		} else {
			$message = __( 'Enter your Content Workflow API credentials. Instructions for getting your API key can be found', 'content-workflow-by-bynder' );
			echo '<p>' . esc_html( $message ) . ' <a href="https://gathercontent.com/developers/authentication/" target="_blank">here</a></p>';
		}
	}

	public function account_email_field_cb( $field ) {
		$id = $field->param( 'id' );

		$this->view(
			'input',
			array(
				'id'    => $id,
				'name'  => $this->option_name . '[' . $id . ']',
				'value' => esc_attr( $this->get_setting( $id ) ),
			)
		);
	}

	public function platform_url_slug_field_cb( $field ) {
		$id = $field->param( 'id' );

		echo '<div class="platform-url-wrap">';

		echo '<div class="platform-url-help gc-domain-prefix">https://</div>';

		$this->view(
			'input',
			array(
				'id'          => $id,
				'name'        => $this->option_name . '[' . $id . ']',
				'value'       => esc_attr( $this->get_setting( $id ) ),
				'placeholder' => 'your-account',
			)
		);

		echo '<div class="platform-url-help gc-domain">.gathercontent.com</div>';

		echo '</div>';
	}

	public function api_key_field_cb( $field ) {
		$id = $field->param( 'id' );

		$this->view(
			'input',
			array(
				'id'          => $id,
				'name'        => $this->option_name . '[' . $id . ']',
				'value'       => esc_attr( $this->get_setting( $id ) ),
				'placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
				'desc'        => '<a href="https://gathercontent.com/developers/authentication/" target="_blank">' . __( 'How to get your API key', 'content-workflow-by-bynder' ) . '</a>',
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-is-migrated',
				'name'  => $this->option_name . '[migrated]',
				'value' => $this->get_setting( 'migrated' ),
			)
		);
	}

	public function auth_username_field_cb( $field ) {
		$id = $field->param( 'id' );

		$enabled = \GatherContent\Importer\auth_enabled();
		$this->view(
			'input',
			array(
				'id'          => $id,
				'name'        => $this->option_name . '[' . $id . ']',
				'value'       => esc_attr( $this->get_setting( $id ) ),
				'placeholder' => is_string( $enabled ) ? esc_attr( $enabled ) : '',
			)
		);
	}

	public function auth_pw_field_cb( $field ) {
		$id = $field->param( 'id' );

		$this->view(
			'input',
			array(
				'id'    => $id,
				'name'  => $this->option_name . '[' . $id . ']',
				'value' => esc_attr( $this->get_setting( $id ) ),
				'type'  => 'password',
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'auth_verified',
				'name'  => $this->option_name . '[auth_verified]',
				'value' => $this->get_setting( 'auth_verified' ),
			)
		);
	}

	public function api_setup_complete() {
		$section = new Form_Section(
			'steps_complete',
			'',
			array( $this, 'api_setup_complete_cb' ),
			self::SLUG
		);
	}

	public function api_setup_complete_cb() {
		if ( $user = $this->api()->get_me() ) {
			if ( isset( $user->first_name ) ) {

				$data = (array) $user;

				$data['message'] = esc_html__( "You've successfully connected to the Content Workflow API", 'content-workflow-by-bynder' );

				$data['avatar'] = ! empty( $data['avatar'] )
					? 'https://gathercontent-production-avatars.s3-us-west-2.amazonaws.com/' . $data['avatar']
					: GATHERCONTENT_URL . 'images/avatar.png';

				if ( $this->set_my_account() ) {

					$data['message'] .= ' ' . sprintf( esc_html__( 'and the %s account.', 'content-workflow-by-bynder' ), '<a href="' . esc_url( $this->platform_url() ) . '" target="_blank">' . esc_html( $this->account->name ) . '</a>' );
				}

				$this->view( 'user-profile', $data );
			}
		}
	}

	/**
	 * Determine if settings need to be migrated from previous version.
	 *
	 * @return mixed Settings key prefix, if old settings are found.
	 * @since  3.0.0
	 *
	 */
	public function should_migrate() {
		if ( $this->get_setting( 'migrated' ) ) {
			return false;
		}

		return $this->prev_option_key();
	}

	/**
	 * Get previous plugin's options key.
	 *
	 * Since previous version used `plugin_basename( __FILE__ )` to determine
	 * the option prefix, we have to check a couple possible variations.
	 *
	 * @return mixed Settings key prefix, if old settings are found.
	 * @since  3.0.0.9
	 *
	 */
	public function prev_option_key() {
		$prefixes = array(
			'content-workflow', // from wordpress.org/plugins/content-workflow-by-bynder
			'wordpress-plugin', // from github.com/Bynder/cw-wordpress-plugin

			'gathercontent-import-old', // local copy
		);

		foreach ( $prefixes as $prefix ) {
			if ( get_option( $prefix . '_api_key' ) && get_option( $prefix . '_api_url' ) ) {
				return $prefix;
			}
		}

		return false;
	}

	public function migrate_settings( $settings ) {
		$key = $this->should_migrate();
		if ( ! $key ) {
			return;
		}

		$settings = get_option( $key . '_saved_settings' );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return;
		}

		$mapped = array();
		foreach ( $settings as $project_id => $items ) {
			if ( empty( $items ) || ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item_id => $setting_data ) {

				if (
					isset( $setting_data['overwrite'] )
					&& $setting_data['overwrite'] > 0
					&& ( $post = get_post( absint( $setting_data['overwrite'] ) ) )
				) {
					// We'll set the mapped item ID, but mappings will still need to be created.
					\GatherContent\Importer\update_post_item_id( $post->ID, absint( $item_id ) );
				}
			}
		}
	}
}
