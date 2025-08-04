<?php

namespace Gravity_Forms\Gravity_SMTP\Translations;

use Gravity_Forms\Gravity_Tools\Utils\Common;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class TranslationsPress {

	const T15S_TRANSIENT_KEY = 't15s-registry-gforms';
	const T15S_API_URL       = 'https://packages.translationspress.com/rocketgenius/packages.json';

	/**
	 * The plugin slug.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Gravity Tools Common Utils.
	 *
	 * @since 1.0
	 *
	 * @var Common
	 */
	private $common;

	/**
	 * The locales installed during the current request.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $installed = array();

	/**
	 * Cached TranslationsPress data for GravitySMTP.
	 *
	 * @since 1.0
	 *
	 * @var null|object
	 */
	private $all_translations;

	/**
	 * All languages available.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $languages = array();

	/**
	 * Whether the translations are refreshed or not.
	 *
	 * @since 1.0
	 *
	 * @var bool
	 */
	private $refreshed = false;

	/**
	 * The installed translations data.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $translations_data = array();

	/**
	 * Adds GravitySMTP to load translations for.
	 *
	 * @since 1.0
	 *
	 * @param string $slug
	 * @param Common $common
	 */
	public function __construct( $slug, $common ) {
		$this->slug   = $slug;
		$this->common = $common;
	}

	/**
	 * Short-circuits translations API requests for private projects.
	 *
	 * @since 1.0
	 *
	 * @param bool|array $result         The result object. Default false.
	 * @param string     $requested_type The type of translations being requested.
	 * @param object     $args           Translation API arguments.
	 *
	 * @return bool|array
	 */
	public function translations_api( $result, $requested_type, $args ) {
		if ( 'plugins' !== $requested_type || $this->slug !== $args['slug'] ) {
			return $result;
		}

		return $this->get_plugin_translations();
	}

	/**
	 * Filters the translations transients to include the current plugin.
	 *
	 * @see wp_get_translation_updates()
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The transient value.
	 *
	 * @return object
	 */
	public function site_transient_update_plugins( $value ) {
		if ( ! $value ) {
			$value = new \stdClass();
		}

		if ( ! isset( $value->translations ) ) {
			$value->translations = array();
		}

		$translations = $this->get_plugin_translations();

		if ( empty( $translations['translations'] ) ) {
			return $value;
		}

		foreach ( $translations['translations'] as $translation ) {
			if ( ! $this->should_install( $translation ) ) {
				continue;
			}

			$translation['type'] = 'plugin';
			$translation['slug'] = $this->slug;

			$value->translations[] = $translation;
		}

		return $value;
	}

	/**
	 * Gets the TranslationsPress data.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_plugin_translations() {
		$this->set_all_translations();

		return (array) $this->common->rgar( $this->all_translations->projects, $this->slug );
	}

	/**
	 * Refreshes the cached TranslationsPress data, if expired.
	 *
	 * @since 1.0
	 */
	public function refresh_all_translations() {
		if ( $this->refreshed ) {
			return;
		}

		$this->all_translations = null;
		$this->set_all_translations();
		$this->refreshed = true;
	}

	/**
	 * Determines if the cached TranslationsPress data needs refreshing.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private function is_transient_expired() {
		$cache_lifespan = 12 * HOUR_IN_SECONDS;

		return ! isset( $this->all_translations->_last_checked ) || ( time() - $this->all_translations->_last_checked ) > $cache_lifespan;
	}

	/**
	 * Gets the translations data from the TranslationsPress API.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_remote_translations_data() {
		$result = json_decode( wp_remote_retrieve_body( wp_remote_get( self::T15S_API_URL, array( 'timeout' => 3 ) ) ), true );

		return is_array( $result ) ? $result : array();
	}

	/**
	 * Caches the TranslationsPress data, if not already cached.
	 *
	 * @since 1.0
	 */
	private function set_all_translations() {
		if ( is_object( $this->all_translations ) ) {
			return;
		}

		$this->all_translations = get_site_transient( self::T15S_TRANSIENT_KEY );
		if ( is_object( $this->all_translations ) && ! $this->is_transient_expired() ) {
			return;
		}

		$this->all_translations                = new \stdClass();
		$this->all_translations->projects      = $this->get_remote_translations_data();
		$this->all_translations->_last_checked = time();
		set_site_transient( self::T15S_TRANSIENT_KEY, $this->all_translations );
	}

	/**
	 * Triggers translation installation when a user updates the site language setting.
	 *
	 * @since 1.0
	 *
	 * @param string $old_language The language before the user changed the site language setting.
	 * @param string $new_language The new language after the user changed the site language setting.
	 */
	public function install_on_wplang_update( $old_language, $new_language ) {
		if ( empty( $new_language ) || ! current_user_can( 'install_languages' ) ) {
			return;
		}

		$this->install( $new_language );
	}

	/**
	 * Triggers translation installation, if required.
	 *
	 * @since 1.0
	 *
	 * @param string $locale The locale when the site locale is changed or an empty string to install all the user available locales.
	 */
	public function install( $locale = '' ) {
		if ( $locale && in_array( $locale, $this->installed ) ) {
			return;
		}

		$translations = $this->get_plugin_translations();

		if ( empty( $translations['translations'] ) ) {
			// Aborting; No translations list for $this->slug.

			return;
		}

		foreach ( $translations['translations'] as $translation ) {
			if ( ! $this->should_install( $translation, $locale ) ) {
				continue;
			}

			$this->install_translation( $translation );

			if ( $locale ) {
				return;
			}
		}
	}

	/**
	 * Downloads and installs the given translation.
	 *
	 * @since 1.0
	 *
	 * @param array $translation The translation data.
	 */
	private function install_translation( $translation ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/admin.php';

			// Same approach as in WP Core: https://github.com/WordPress/wordpress-develop/blob/6.2/src/wp-includes/rest-api/endpoints/class-wp-rest-plugins-controller.php#L853-L869.
			ob_start();
			$filesystem_credentials_available = request_filesystem_credentials( self_admin_url() );
			ob_end_clean();

			if ( ! $filesystem_credentials_available ) {
				// Aborting; filesystem credentials required.
				return;
			}

			if ( ! \WP_Filesystem() ) {
				// Aborting; unable to init WP_Filesystem.
				return;
			}
		}

		$lang_dir = $this->get_path();
		if ( ! $wp_filesystem->is_dir( $lang_dir ) ) {
			$wp_filesystem->mkdir( $lang_dir, FS_CHMOD_DIR );
		}

		// Downloading: $translation['package'].
		$temp_file = download_url( $translation['package'] );

		if ( is_wp_error( $temp_file ) ) {
			// Error downloading package. Code: $temp_file->get_error_code(); Message: $temp_file->get_error_message().

			return;
		}

		$zip_path    = $lang_dir . $this->slug . '-' . $translation['language'] . '.zip';
		$copy_result = $wp_filesystem->copy( $temp_file, $zip_path, true, FS_CHMOD_FILE );
		$wp_filesystem->delete( $temp_file );

		if ( ! $copy_result ) {
			// Unable to move package to: $lang_dir.

			return;
		}

		$result = unzip_file( $zip_path, $lang_dir );
		@unlink( $zip_path );

		if ( is_wp_error( $result ) ) {
			// Error extracting package. Code: $result->get_error_code(); Message: $result->get_error_message().

			return;
		}

		// Installed $translation['language'] translation for $this->slug.
		$this->installed[] = $translation['language'];
	}

	/**
	 * Logs which locales WordPress installs translations for.
	 *
	 * @since 1.0
	 *
	 * @param object $upgrader_object WP_Upgrader Instance.
	 * @param array  $hook_extra      Item update data.
	 */
	public function upgrader_process_complete( $upgrader_object, $hook_extra ) {
		if (
			$this->common->rgar( $hook_extra, 'action' ) !== 'install' ||
			$this->common->rgar( $hook_extra, 'type' ) !== 'plugin' ||
			empty( $upgrader_object->result ) ||
			is_wp_error( $upgrader_object->result )
		) {
			return;
		}

		$slug = $this->common->rgar( $upgrader_object->result, 'destination_name' );

		if ( empty( $slug ) && ! empty( $upgrader_object->new_plugin_data ) ) {
			$slug = $this->common->rgar( $upgrader_object->new_plugin_data, 'TextDomain' );
		}

		if ( $slug !== $this->slug ) {
			return;
		}

		$this->install();
	}

	/**
	 * Returns an array of locales the site has installed.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_available_languages() {
		if ( empty( $this->languages ) ) {
			$this->languages = get_available_languages();
		}

		return $this->languages;
	}

	/**
	 * Returns the header data from the installed translations for the current plugin.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_installed_translations_data() {
		if ( isset( $this->translations_data[ $this->slug ] ) ) {
			return $this->translations_data[ $this->slug ];
		}

		$this->translations_data[ $this->slug ] = array();
		$translations                           = $this->get_installed_translations( true );

		foreach ( $translations as $locale => $mo_file ) {
			$po_file = str_replace( '.mo', '.po', $mo_file );
			if ( ! file_exists( $po_file ) ) {
				continue;
			}
			$this->translations_data[ $this->slug ][ $locale ] = wp_get_pomo_file_data( $po_file );
		}

		return $this->translations_data[ $this->slug ];
	}

	/**
	 * Returns an array of locales or mo translation files found in the WP_LANG_DIR/plugins directory.
	 *
	 * @since 1.0
	 *
	 * @param bool $return_files Indicates if the mo files should be returned using the locales as the keys.
	 *
	 * @return array
	 */
	public function get_installed_translations( $return_files = false ) {
		$files = glob( $this->get_path() . $this->slug . '-*.mo' );

		if ( ! is_array( $files ) ) {
			return array();
		}

		$translations = array();

		foreach ( $files as $file ) {
			$translations[ str_replace( $this->slug . '-', '', basename( $file, '.mo' ) ) ] = $file;
		}

		return $return_files ? $translations : array_keys( $translations );
	}

	/**
	 * Returns the path to where the plugin translations are stored.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private function get_path() {
		return WP_LANG_DIR . '/plugins/';
	}

	/**
	 * Determines if a translation should be installed.
	 *
	 * @since 1.0
	 *
	 * @param array  $translation The translation data.
	 * @param string $locale      The locale when the site locale is changed or an empty string to check all the user available locales.
	 *
	 * @return bool
	 */
	private function should_install( $translation, $locale = '' ) {
		if ( ( $locale && $locale !== $translation['language'] ) || ! in_array( $translation['language'], $this->get_available_languages() ) ) {
			return false;
		}

		if ( empty( $translation['updated'] ) ) {
			return true;
		}

		$installed = $this->get_installed_translations_data();

		if ( ! isset( $installed[ $translation['language'] ] ) ) {
			return true;
		}

		$local  = date_create( $installed[ $translation['language'] ]['PO-Revision-Date'] );
		$remote = date_create( $translation['updated'] );

		return $remote > $local;
	}

}
