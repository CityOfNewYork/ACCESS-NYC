<?php

namespace OTGS\Installer\AdminNotices\Notices;

use OTGS\Installer\Collection;
use OTGS\Installer\Recommendations\RecommendationsManager;
use OTGS\Installer\Recommendations\Storage;
use function OTGS\Installer\FP\partial;

class Recommendation {

	const PLUGIN_ACTIVATED = 'plugin-activated';

	public static function addHooks() {
		add_filter( 'otgs_installer_admin_notices_config', self::class . '::config' );
		add_filter( 'otgs_installer_admin_notices_texts', self::class . '::texts' );
		add_filter( 'otgs_installer_admin_notices_dismissions', self::class . '::dismissions' );
		add_filter(
			'otgs_installer_admin_notices',
			self::class . '::getCurrentNotices'
		);
	}

	/**
	 * @param array $initialNotices
	 *
	 * @return array
	 */
	public static function getCurrentNotices( array $initialNotices ) {
		$activatedPluginsConfig = apply_filters('wpml_installer_get_stored_recommendation_notices', []);
		$addNoticeIdField       = function ( $item ) {
			$item['noticeId'] = self::PLUGIN_ACTIVATED . '-' . $item['glue_check_slug'];

			return $item;
		};

		$updatedConfig = Collection::of( $activatedPluginsConfig )
		                           ->map( function ( $items ) use ( $addNoticeIdField ) {
			                           $items = Collection::of( $items )->map( $addNoticeIdField )->get();

			                           return [ self::PLUGIN_ACTIVATED => $items ];
		                           } );

		if ( ! empty( $updatedConfig->get() ) ) {
			$activatedPluginsConfig = [ 'repo' => $updatedConfig->get() ];
		} else {
			$activatedPluginsConfig = [];
		}

		return array_merge_recursive( $initialNotices, $activatedPluginsConfig );
	}

	/**
	 * @param array $initialConfig
	 *
	 * @return array
	 */
	public static function config( array $initialConfig ) {
		return self::screens( $initialConfig );
	}

	/**
	 * @param array $screens
	 *
	 * @return array
	 */
	public static function screens( array $screens ) {
		$config = [
			self::PLUGIN_ACTIVATED => [ 'screens' => [ 'plugins' ] ],
		];

		return array_merge_recursive( $screens, [
			'repo' => [
				'wpml' => $config,
			],
		] );
	}

	/**
	 * @param array $initialTexts
	 *
	 * @return array
	 */
	public static function texts( array $initialTexts ) {
		return array_merge_recursive(
			$initialTexts,
			[
				'repo' => [
					'wpml' => [
						self::PLUGIN_ACTIVATED => WPMLTexts::class . '::pluginActivatedRecommendation',
					],
				],
			]
		);
	}

	/**
	 * @param array $initialDismissions
	 *
	 * @return array
	 */
	public static function dismissions( array $initialDismissions ) {
		return array_merge_recursive(
			$initialDismissions,
			[
				self::PLUGIN_ACTIVATED => Dismissions::class . '::dismissRecommendationNotice',
			]
		);
	}
}
