<?php

namespace Gravity_Forms\Gravity_Tools\Updates;

use Gravity_Forms\Gravity_Tools\License\License_API_Connector;
use Gravity_Forms\Gravity_Tools\Utils\Common;

if ( ! defined( 'RG_CURRENT_PAGE' ) ) {
	define( 'RG_CURRENT_PAGE', '' );
}

class Auto_Updater {

	protected $_version;
	protected $_slug;
	protected $_title;
	protected $_update_icon;
	protected $_full_path;
	protected $_path;
	protected $_url;
	protected $_is_forms;

	/**
	 * @var Common
	 */
	protected $common;

	/**
	 * @var License_API_Connector
	 */
	protected $license_connector;


	public function __construct( $slug, $version, $title, $full_path, $path, $url, $update_icon, $common, $license_connector, $is_forms = true ) {
		$this->_slug             = $slug;
		$this->_version          = $version;
		$this->_title            = $title;
		$this->_full_path        = $full_path;
		$this->_path             = $path;
		$this->_url              = $url;
		$this->_update_icon      = $update_icon;
		$this->common            = $common;
		$this->license_connector = $license_connector;
		$this->_is_forms         = $is_forms;
	}

	/**
	 * Initialize various hooks and filters.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'install_plugins_pre_plugin-information', array( $this, 'display_changelog' ), 9 );
			add_action( 'gform_after_check_update', array( $this, 'flush_version_info' ) );
			add_action( 'gform_updates', array( $this, 'display_updates' ) );
			if ( $this->_is_forms ) {
				add_filter( 'gform_updates_list', array( $this, 'get_update_info' ) );
			}

			if ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {
				add_action( 'wp_ajax_gf_get_changelog', array( $this, 'ajax_display_changelog' ) );
			}
		}

		// Check for updates. The check might not run the admin context. E.g. from WP-CLI.
		add_filter( 'transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );

		// ManageWP premium update filters
		add_filter( 'mwp_premium_update_notification', array( $this, 'premium_update_push' ) );
		add_filter( 'mwp_premium_perform_update', array( $this, 'premium_update' ) );
	}

	/**
	 * Premium update push for ManageWP.
	 *
	 * @since 1.0
	 *
	 * @filter mwp_premium_update_notification 10 1
	 *
	 * @param $premium_update
	 *
	 * @return mixed
	 */
	public function premium_update_push( $premium_update ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$update = $this->get_version_info( $this->_slug );
		if ( $this->common->rgar( $update, 'is_valid_key' ) == true && version_compare( $this->_version, $update['version'], '<' ) ) {
			$plugin_data                = get_plugin_data( $this->_full_path );
			$plugin_data['type']        = 'plugin';
			$plugin_data['slug']        = $this->_path;
			$plugin_data['new_version'] = isset( $update['version'] ) ? $update['version'] : false;
			$premium_update[]           = $plugin_data;
		}

		return $premium_update;
	}

	/**
	 * Integrate with ManageWP
	 *
	 * @since 1.0
	 *
	 * @filter mwp_premium_perform_update 10 1
	 *
	 * @param $premium_update
	 *
	 * @return mixed
	 */
	public function premium_update( $premium_update ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$update = $this->get_version_info( $this->_slug );
		if ( $this->common->rgar( $update, 'is_valid_key' ) == true && version_compare( $this->_version, $update['version'], '<' ) ) {
			$plugin_data         = get_plugin_data( $this->_full_path );
			$plugin_data['slug'] = $this->_path;
			$plugin_data['type'] = 'plugin';
			$plugin_data['url']  = isset( $update['url'] ) ? $update['url'] : false; // OR provide your own callback function for managing the update

			array_push( $premium_update, $plugin_data );
		}

		return $premium_update;
	}


	/**
	 * Flush the currently-stored version info.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function flush_version_info() {
		$this->set_version_info( $this->_slug, false );
	}

	/**
	 * Set version info in a transient.
	 *
	 * @since 1.0
	 *
	 * @param $plugin_slug
	 * @param $version_info
	 *
	 * @return void]
	 */
	private function set_version_info( $plugin_slug, $version_info ) {
		if ( function_exists( 'set_site_transient' ) ) {
			set_site_transient( $plugin_slug . '_version', $version_info, 60 * 60 * 12 );
		} else {
			set_transient( $plugin_slug . '_version', $version_info, 60 * 60 * 12 );
		}
	}

	/**
	 * Check for updates.
	 *
	 * @since 1.0
	 *
	 * @filter transient_update_plugins      10 1
	 * @filter site_transient_update_plugins 10 1
	 *
	 * @param $option
	 *
	 * @return mixed
	 */
	public function check_update( $option ) {

		if ( empty( $option ) ) {
			return $option;
		}

		$key = $this->get_key();

		$version_info = $this->get_version_info( $this->_slug );

		if ( $this->common->rgar( $version_info, 'is_error' ) == '1' ) {
			return $option;
		}

		if ( empty( $option->response[ $this->_path ] ) ) {
			$option->response[ $this->_path ] = new \stdClass();
		}

		$plugin = array(
			'plugin'      => $this->_path,
			'url'         => $this->_url,
			'slug'        => $this->_slug,
			'icons' => array(
				'2x' => $this->_update_icon,
			),
			'package'     => is_string( $version_info['url'] ) ? str_replace( '{KEY}', $key, $version_info['url'] ) : '',
			'new_version' => $version_info['version'],
			'id'          => '0',
		);

		//Empty response means that the key is invalid. Do not queue for upgrade
		if ( ! $this->common->rgar( $version_info, 'is_valid_key' ) || version_compare( $this->_version, $version_info['version'], '>=' ) ) {
			unset( $option->response[ $this->_path ] );
			$option->no_update[ $this->_path ] = (object) $plugin;
		} else {
			$option->response[ $this->_path ] = (object) $plugin;
		}

		return $option;

	}

	/**
	 * Display the changelog.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function display_changelog() {
		if ( $_REQUEST['plugin'] != $this->_slug ) {
			return;
		}
		$change_log = $this->get_changelog();
		echo $change_log;

		exit;
	}

	/**
	 * Get changelog with admin-ajax.php in GFForms::maybe_display_update_notification().
	 *
	 * @since 2.4.15
	 *
	 * @return void
	 */
	public function ajax_display_changelog() {
		check_admin_referer();

		$this->display_changelog();
	}

	/**
	 * Get the changelog content.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private function get_changelog() {
		$key                = $this->get_key();
		$body               = "key={$key}";
		$options            = array( 'method' => 'POST', 'timeout' => 3, 'body' => $body );
		$options['headers'] = array(
			'Content-Type'   => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
			'Content-Length' => strlen( $body ),
			'User-Agent'     => 'WordPress/' . get_bloginfo( 'version' ),
		);

		$raw_response = $this->common->post_to_manager( 'changelog.php', $this->get_remote_request_params( $this->_slug, $key, $this->_version ), $options );

		if ( is_wp_error( $raw_response ) || 200 != $raw_response['response']['code'] ) {
			$text = sprintf( esc_html__( 'Oops!! Something went wrong.%sPlease try again or %scontact us%s.', 'gravityforms' ), '<br/>', "<a href='" . esc_attr( $this->common->get_support_url() ) . "'>", '</a>' );
		} else {
			$text = $raw_response['body'];
			if ( substr( $text, 0, 10 ) != '<!--GFM-->' ) {
				$text = '';
			}
		}

		return stripslashes( $text );
	}

	private function get_version_info( $offering, $use_cache = true ) {
		$version_info = $this->license_connector->get_version_info( $use_cache );
		$is_valid_key = $this->common->rgar( $version_info, 'is_valid_key' ) && $this->common->rgars( $version_info, "offerings/{$offering}/is_available" );

		$info = array(
			'is_valid_key' => $is_valid_key,
			'version'      => $this->common->rgars( $version_info, "offerings/{$offering}/version" ),
			'url'          => $this->common->rgars( $version_info, "offerings/{$offering}/url" )
		);

		return $info;
	}

	private function get_remote_request_params( $offering, $key, $version ) {
		global $wpdb;

		return sprintf( 'of=%s&key=%s&v=%s&wp=%s&php=%s&mysql=%s', urlencode( $offering ), urlencode( $key ), urlencode( $version ), urlencode( get_bloginfo( 'version' ) ), urlencode( phpversion() ), urlencode( $this->common->get_db_version() ) );
	}

	private function get_key() {
		return $this->common->get_key();
	}

	/**
	 * Get the update info.
	 *
	 * @since 1.0
	 *
	 * @param $updates
	 *
	 * @return mixed
	 */
	public function get_update_info( $updates ) {

		$force_check  = rgget( 'force-check' ) == 1;
		$version_info = $this->get_version_info( $this->_slug, ! $force_check );

		$plugin_file = $this->_path;
		$upgrade_url = wp_nonce_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $plugin_file ), 'upgrade-plugin_' . $plugin_file );

		if ( ! $this->common->rgar( $version_info, 'is_valid_key' ) ) {

			$version_icon    = 'dashicons-no';
			$version_message = sprintf(
				'<p>%s</p>',
				sprintf(
					esc_html( '%sRegister%s your copy of Gravity Forms to receive access to automatic updates and support. Need a license key? %sPurchase one now%s.', 'gravityforms' ),
					'<a href="admin.php?page=gf_settings">',
					'</a>',
					'<a href="https://www.gravityforms.com">',
					'</a>'
				)
			);

		} elseif ( version_compare( $this->_version, $version_info['version'], '<' ) ) {

			$details_url       = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . urlencode( $this->_slug ) . '&section=changelog&TB_iframe=true&width=600&height=800' );
			$message_link_text = sprintf( esc_html__( 'View version %s details', 'gravityforms' ), $version_info['version'] );
			$message_link      = sprintf( '<a href="%s" class="thickbox" title="%s">%s</a>', esc_url( $details_url ), esc_attr( $this->_title ), $message_link_text );
			$message           = sprintf( esc_html__( 'There is a new version of %1$s available. %s.', 'gravityforms' ), $this->_title, $message_link );

			$version_icon    = 'dashicons-no';
			$version_message = $message;

		} else {

			$version_icon    = 'dashicons-yes';
			$version_message = sprintf( esc_html__( 'Your version of %s is up to date.', 'gravityforms' ), $this->_title );
		}

		$updates[] = array(
			'name'              => esc_html( $this->_title ),
			'is_valid_key'      => $this->common->rgar( $version_info, 'is_valid_key' ),
			'path'              => $this->_path,
			'slug'              => $this->_slug,
			'latest_version'    => $version_info['version'],
			'installed_version' => $this->_version,
			'upgrade_url'       => $upgrade_url,
			'download_url'      => $version_info['url'],
			'version_icon'      => $version_icon,
			'version_message'   => $version_message,
		);

		return $updates;

	}

	/**
	 * Display updates if necessary.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function display_updates() {

		?>
		<div class="wrap <?php echo $this->common->get_browser_class() ?>">
			<h2><?php esc_html_e( $this->_title ); ?></h2>
			<?php
			$force_check  = rgget( 'force-check' ) == 1;
			$version_info = $this->get_version_info( $this->_slug, ! $force_check );

			if ( ! $this->common->rgar( $version_info, 'is_valid_key' ) ) {
				?>
				<div class="gf_update_expired alert_red">
					<?php printf( esc_html__( '%sRegister%s your copy of %s to receive access to automatic updates and support. Need a license key? %sPurchase one now%s.', 'gravityforms' ), '<a href="admin.php?page=gf_settings">', '</a>', $this->_title, '<a href="https://www.gravityforms.com">', '</a>' ); ?>
				</div>

				<?php
			} elseif ( version_compare( $this->_version, $version_info['version'], '<' ) ) {

				if ( $this->common->rgar( $version_info, 'is_valid_key' ) ) {
					$plugin_file       = $this->_path;
					$upgrade_url       = wp_nonce_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $plugin_file ), 'upgrade-plugin_' . $plugin_file );
					$details_url       = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . urlencode( $this->_slug ) . '&section=changelog&TB_iframe=true&width=600&height=800' );
					$message_link_text = sprintf( esc_html__( 'View version %s details', 'gravityforms' ), $version_info['version'] );
					$message_link      = sprintf( '<a href="%s" class="thickbox" title="%s">%s</a>', esc_url( $details_url ), esc_attr( $this->_title ), $message_link_text );
					$message           = sprintf( esc_html__( 'There is a new version of %1$s available. %s.', 'gravityforms' ), $this->_title, $message_link );

					?>
					<div class="gf_update_outdated alert_yellow">
						<?php echo $message . ' <p>' . sprintf( esc_html__( 'You can update to the latest version automatically or download the update and install it manually. %sUpdate Automatically%s %sDownload Update%s', 'gravityforms' ), "</p><a class='button-primary' href='{$upgrade_url}'>", '</a>', "&nbsp;<a class='button' href='{$version_info['url']}'>", '</a>' ); ?>
					</div>
					<?php
				}
			} else {

				?>
				<div class="gf_update_current alert_green">
					<?php printf( esc_html__( 'Your version of %s is up to date.', 'gravityforms' ), $this->_title ); ?>
				</div>
				<?php
			}

			?>

		</div>
		<?php
	}
}
