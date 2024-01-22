<?php

namespace WPML;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\Setup\Option;

class Plugins {
	const WPML_TM_PLUGIN              = 'wpml-translation-management/plugin.php';
	const WPML_CORE_PLUGIN            = 'sitepress-multilingual-cms/sitepress.php';
	const WPML_SUBSCRIPTION_TYPE_BLOG = 6718;
	const AFTER_INSTALLER             = 999;

	public static function loadCoreFirst() {
		$plugins = get_option( 'active_plugins' );

		$isSitePress = function ( $value ) {
			return $value === WPML_PLUGIN_BASENAME;
		};

		$newOrder = wpml_collect( $plugins )
			->prioritize( $isSitePress )
			->values()
			->toArray();

		if ( $newOrder !== $plugins ) {
			update_option( 'active_plugins', $newOrder );
		}
	}

	public static function isTMAllowed() {
		$isTMAllowed = true;

		if ( function_exists( 'OTGS_Installer' ) ) {
			$subscriptionType = OTGS_Installer()->get_subscription( 'wpml' )->get_type();
			if ( $subscriptionType && $subscriptionType === self::WPML_SUBSCRIPTION_TYPE_BLOG ) {
				$isTMAllowed = false;
			}
		}

		return $isTMAllowed;
	}

	public static function updateTMAllowedOption() {
		$isTMAllowed = self::isTMAllowed();
		Option::setTMAllowed( $isTMAllowed );
		return $isTMAllowed;
	}

	public static function updateTMAllowedAndTranslateEverythingOnSubscriptionChange() {
		if ( function_exists( 'OTGS_Installer' ) ) {
			$type = OTGS_Installer()->get_subscription( 'wpml' )->get_type();
			if ( $type ) {
				Option::setTMAllowed( $type !== self::WPML_SUBSCRIPTION_TYPE_BLOG );
				if ( self::WPML_SUBSCRIPTION_TYPE_BLOG === $type ) {
					Option::setTranslateEverything( false );
				}
			}
		}
	}

	/**
	 * @param bool $isSetupComplete
	 */
	public static function loadEmbeddedTM( $isSetupComplete ) {
		$tmSlug  = 'wpml-translation-management/plugin.php';

		self::stopPluginActivation( self::WPML_TM_PLUGIN );
		add_action( 'otgs_installer_subscription_refreshed', [ self::class, 'updateTMAllowedOption' ] );

		if ( ! self::deactivateTm() ) {

			add_action( "after_plugin_row_$tmSlug", [ self::class, 'showEmbeddedTMNotice' ] );
			add_action( 'otgs_installer_initialized', [ self::class,
				'updateTMAllowedAndTranslateEverythingOnSubscriptionChange'
			] );

			$isTMAllowed = Option::isTMAllowed();
			if ( $isTMAllowed === null ) {
				add_action( 'after_setup_theme', [ self::class, 'updateTMAllowedOption' ], self::AFTER_INSTALLER );
			}
			if ( ! $isSetupComplete || $isTMAllowed ) {
				require_once WPML_PLUGIN_PATH . '/tm.php';
			}
		}
	}

	private static function deactivateTm() {
		if ( ! self::isTMActive() ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-includes/pluggable.php';

		deactivate_plugins( self::WPML_TM_PLUGIN );

		if ( ! wpml_is_cli() && ! wpml_is_ajax() && wp_redirect( $_SERVER['REQUEST_URI'], 302, 'WPML' ) ) {
			exit;
		}

		return true;
	}

	public static function isTMActive() {
		$hasTM = function ( $plugins ) {
			return is_array( $plugins ) && (
					Lst::includes( self::WPML_TM_PLUGIN, $plugins ) || // 'active_plugins' stores plugins as values
					array_key_exists( self::WPML_TM_PLUGIN, $plugins ) // 'active_sitewide_plugins' stores plugins as keys
				);
		};

		if ( \is_multisite() && $hasTM( \get_site_option( 'active_sitewide_plugins', [] ) ) ) {
			return true;
		}

        return $hasTM( \get_option( 'active_plugins', [] ) );
	}

	private static function stopPluginActivation( $pluginSlug ) {
		if ( Relation::propEq( 'action', 'activate', $_GET ) && Relation::propEq( 'plugin', $pluginSlug, $_GET ) ) {
			unset( $_GET['plugin'], $_GET['action'] );
		}

		if ( wpml_is_cli() ) {
			if ( Lst::includesAll( [ 'plugin', 'activate', 'wpml-translation-management' ], $_SERVER['argv'] ) ) {
				\WP_CLI::warning(
					__( 'WPML Translation Management is now included in WPML Multilingual CMS.', 'sitepress' )
				);
			}
		}

		if (
			Relation::propEq( 'action', 'activate-selected', $_POST )
			&& Lst::includes( $pluginSlug, Obj::propOr( [], 'checked', $_POST ) )
		) {
			$_POST['checked'] = Fns::reject( Relation::equals( $pluginSlug ), $_POST['checked'] );
		}
	}

	public static function showEmbeddedTMNotice() {
		$wpListTable = _get_list_table( 'WP_Plugins_List_Table' );
		?>

		<tr class="plugin-update-tr">
			<td colspan="<?php echo $wpListTable->get_column_count(); ?>" class="plugin-update colspanchange">
				<div class="update-message inline notice notice-error notice-alt">
					<p>
						<?php
						echo _e(
							'This plugin has been deactivated as it is now part of the WPML Multilingual CMS plugin. You can safely delete it.',
							'sitepress'
						);
						$readMoreLink = 'https://wpml.org/changelog/2021/10/wpml-4-5-translate-all-of-your-sites-content-with-one-click/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore#fewer-plugins-to-manage';
						?>
						<a href="<?php echo $readMoreLink; ?>" target="_blank" class="wpml-external-link">
							<?php _e( 'Read more', 'sitepress' ); ?>
						</a>
					</p>
				</div>
		</tr>
		<?php
	}
}
