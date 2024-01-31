<?php

namespace OTGS\Installer\AdminNotices;

use OTGS\Installer\FP\Obj;

class Dismissed {
	const STORE_KEY = 'dismissed';

	/**
	 * @param array $dismissedNotices
	 * @param string $repo
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function isDismissed( array $dismissedNotices, $repo, $id ) {
		return isset( $dismissedNotices['repo'][ $repo ][ $id ] );
	}

	/**
	 * @param string $plugin_slug
	 * @param bool $network
	 * @return void
	 */
	public static function dismissNoticeOnPluginActivation( $plugin_slug, $network ) {
		$repositoryRecommendations = Obj::propOr([], 'repo', apply_filters( 'otgs_installer_admin_notices', [] ) );

		$isPluginRecommendation = function( $plugin_attrs ) use ( $plugin_slug ) {
			return str_contains($plugin_slug, $plugin_attrs['glue_plugin_slug']);
		};
		foreach( $repositoryRecommendations as $repository => $notices ) {
			if ( ! isset( $notices['plugin-activated'] ) ) {
				continue;
			}
			$pluginRecommendationsToDisable = array_filter( $notices['plugin-activated'], $isPluginRecommendation );

			foreach ( $pluginRecommendationsToDisable as $plugin => $recommendation ) {
				self::dismissNoticeByTypeAndRepository( $repository, 'plugin-activated', $plugin );
			}
		}
	}

	/**
	 * @param array $dismissedNotices
	 * @param callable $timeOut - int -> string -> string -> bool
	 *
	 * @return mixed
	 */
	public static function clearExpired( array $dismissedNotices, callable $timeOut ) {
		if ( isset( $dismissedNotices['repo'] ) ) {

			foreach ( $dismissedNotices['repo'] as $repo => $ids ) {
				foreach ( $ids as $id => $dismissedTimeStamp ) {
					if ( $timeOut( $dismissedTimeStamp, $repo, $id ) ) {
						unset ( $dismissedNotices['repo'][ $repo ][ $id ] );
					}
				}
			}
		}

		return $dismissedNotices;
	}

	public static function dismissNotice() {
		$rawData = filter_var_array( $_POST, [
			'repository'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'noticeType'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'noticePluginSlug' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		] );

		self::dismissNoticeByTypeAndRepository(
			Obj::propOr('', 'repository', $rawData ),
			Obj::propOr('', 'noticeType', $rawData ),
			Obj::propOr('', 'noticePluginSlug', $rawData )
		);

		wp_send_json_success( [] );
	}

	public static function dismissRecommendationNoticeByPluginSlug( $dismissed, $data ) {
		$dismissed['repo'][ $data['repository'] ][ $data['noticePluginSlug'] ] = time();
		return $dismissed;
	}

	/**
	 * @param string $dismissRepository
	 * @param string $dismissNoticeType
	 * @param string $dismissNoticePluginSlug
	 * @return void
	 */
	private static function dismissNoticeByTypeAndRepository($dismissRepository, $dismissNoticeType, $dismissNoticePluginSlug) {
		$dismissions = apply_filters('otgs_installer_admin_notices_dismissions', []);

		$store = new Store();

		$dismissed = $store->get(self::STORE_KEY, []);

		$data = [
			'repository' => $dismissRepository,
			'noticeType' => $dismissNoticeType,
			'noticePluginSlug' => $dismissNoticePluginSlug,
		];

		$dismissed = $dismissions[$dismissNoticeType]($dismissed, $data);

		$store->save(self::STORE_KEY, $dismissed);
	}

}
