<?php

namespace OTGS\Installer\AdminNotices\Notices;

use OTGS\Installer\AdminNotices\Loader;
use OTGS\Installer\AdminNotices\Store;
use OTGS\Installer\AdminNotices\ToolsetConfig;
use OTGS\Installer\AdminNotices\WPMLConfig;
use OTGS\Installer\Collection;
use function OTGS\Installer\FP\partial;

class Account {

	const NOT_REGISTERED = 'not-registered';
	const EXPIRED = 'expired';
	const REFUNDED = 'refunded';
	const GET_FIRST_INSTALL_TIME = 'get_first_install_time';
	const DEVELOPMENT_MODE = 'development_mode';

	/**
	 * @param \WP_Installer $installer
	 * @param array $initialNotices
	 *
	 * @return array
	 */
	public static function getCurrentNotices( \WP_Installer $installer, array $initialNotices ) {

		$config = $installer->get_site_key_nags_config();

		$noticeTypes = [
			self::NOT_REGISTERED   => [ Account::class, 'shouldShowNotRegistered' ],
			self::EXPIRED          => [ Account::class, 'shouldShowExpired' ],
			self::REFUNDED         => [ Account::class, 'shouldShowRefunded' ],
			self::DEVELOPMENT_MODE => [ Account::class, 'shouldShowDevelopmentBanner' ],
		];

		return collection::of( $noticeTypes )
		                 ->entities()
		                 ->reduce( Notice::addNoticesForType( $installer, $config ), Collection::of( $initialNotices ) )
		                 ->get();

	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowNotRegistered( \WP_Installer $installer, array $nag ) {
		$shouldShow = ! self::isDevelopmentSite( $installer->get_installer_site_url( $nag['repository_id'] ) ) &&
		              ! $installer->repository_has_subscription( $nag['repository_id'] ) &&
		              ( isset( $nag['condition_cb'] ) ? $nag['condition_cb']() : true );

		if ( $shouldShow ) {
			$shouldShow = ! self::maybeDelayOneWeekOnNewInstalls( $nag['repository_id'] );
		}

		return $shouldShow;
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowExpired( \WP_Installer $installer, array $nag ) {
		return $installer->repository_has_expired_subscription( $nag['repository_id'], 30 * DAY_IN_SECONDS );
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowDevelopmentBanner( \WP_Installer $installer, array $nag ) {
		$showDevelopmentBanner = $installer->repository_has_development_site_key( $nag['repository_id'] );
		$isDismissed = Loader::isDismissed( $nag[ 'repository_id' ], Account::DEVELOPMENT_MODE );
		if ( $showDevelopmentBanner && $isDismissed && $nag['repository_id'] === 'wpml' ) {
			wp_enqueue_style( 'installer-admin-notices', $installer->res_url() . '/res/css/admin-notices.css', array(), $installer->version() );
			add_action( 'wp_before_admin_bar_render', [ static::class, 'addWpmlDevelopmentAdminBar' ], 100);
		}
		return $showDevelopmentBanner;
	}

	public static function addWpmlDevelopmentAdminBar() {
		/** @var \WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar;

		$helpText = __( 'This site is registered on wpml.org as a development site.', 'installer' );
		$text = __( 'Development Site', 'installer' );

		$wp_admin_bar->add_menu(
			array(
				'parent' => false,
				'id'     => 'otgs-wpml-development',
				'title'  => '<i  class="otgs-ico-sitepress-multilingual-cms js-otgs-popover-tooltip" data-tippy-zIndex="999999" title="' . $helpText . '" > ' . $text . '</i>',
				'href'   => false,
			)
		);
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowRefunded( \WP_Installer $installer, array $nag ) {
		return $installer->repository_has_refunded_subscription( $nag['repository_id'] );
	}

	public static function config( array $initialConfig ) {
		return self::pages( self::screens( $initialConfig ) );
	}

	public static function pages( array $initialPages ) {
		$wpmlPages    = [ 'pages' => WPMLConfig::pages() ];
		$toolsetPages = [ 'pages' => ToolsetConfig::pages() ];

		return array_merge_recursive( $initialPages, [
			'repo' => [
				'wpml'    => [
					Account::NOT_REGISTERED   => $wpmlPages,
					Account::EXPIRED          => $wpmlPages,
					Account::REFUNDED         => $wpmlPages,
					Account::DEVELOPMENT_MODE => $wpmlPages,
				],
				'toolset' => [
					Account::NOT_REGISTERED   => $toolsetPages,
					Account::EXPIRED          => $toolsetPages,
					Account::REFUNDED         => $toolsetPages,
					Account::DEVELOPMENT_MODE => $toolsetPages,
				],
			],
		] );
	}

	public static function screens( array $screens ) {
		$config = [
			Account::NOT_REGISTERED   => [ 'screens' => [ 'plugins' ] ],
			Account::EXPIRED          => [ 'screens' => [ 'plugins' ] ],
			Account::REFUNDED         => [ 'screens' => [ 'plugins', 'dashboard' ] ],
			Account::DEVELOPMENT_MODE => [ 'screens' => [ 'plugins', 'dashboard', 'plugin-install' ] ],
		];

		return array_merge_recursive( $screens, [
			'repo' => [
				'wpml'    => $config,
				'toolset' => $config,
			],
		] );
	}

	public static function texts( array $initialTexts ) {
		return array_merge_recursive( $initialTexts, [
			'repo' => [
				'wpml'    => [
					Account::NOT_REGISTERED   => WPMLTexts::class . '::notRegistered',
					Account::EXPIRED          => WPMLTexts::class . '::expired',
					Account::REFUNDED         => WPMLTexts::class . '::refunded',
					Account::DEVELOPMENT_MODE => WPMLTexts::class . '::developmentBanner',
				],
				'toolset' => [
					Account::NOT_REGISTERED   => ToolsetTexts::class . '::notRegistered',
					Account::EXPIRED          => ToolsetTexts::class . '::expired',
					Account::REFUNDED         => ToolsetTexts::class . '::refunded',
					Account::DEVELOPMENT_MODE => ToolsetTexts::class . '::developmentBanner',
				],
			],
		] );
	}

	public static function dismissions( array $initialDismissions ) {
		return array_merge_recursive(
			$initialDismissions,
			[
				Account::NOT_REGISTERED => Dismissions::class . '::dismissAccountNotice',
				Account::DEVELOPMENT_MODE => Dismissions::class . '::dismissAccountNotice',
				Account::EXPIRED        => Dismissions::class . '::dismissAccountNotice',
				Account::REFUNDED       => Dismissions::class . '::dismissAccountNotice',
			]
		);
	}

	private static function isDevelopmentSite( $url ) {
		$endsWith = function ( $haystack, $needle ) {
			return substr_compare( $haystack, $needle, - strlen( $needle ) ) === 0;
		};

		$host = parse_url( $url, PHP_URL_HOST );

		return $endsWith( $host, '.dev' ) ||
		       $endsWith( $host, '.local' ) ||
		       $endsWith( $host, '.test' );
	}

	private static function maybeDelayOneWeekOnNewInstalls( $repo ) {
		$store       = new Store();
		$installTime = $store->get( self::GET_FIRST_INSTALL_TIME, [] );
		if ( ! isset( $installTime[ $repo ] ) ) {
			$installTime[ $repo ] = time();
			$store->save( self::GET_FIRST_INSTALL_TIME, $installTime );
		}

		return time() - $installTime[ $repo ] < WEEK_IN_SECONDS;
	}
}
