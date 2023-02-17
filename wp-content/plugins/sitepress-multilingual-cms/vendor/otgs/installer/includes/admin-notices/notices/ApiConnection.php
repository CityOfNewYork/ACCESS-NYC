<?php

namespace OTGS\Installer\AdminNotices\Notices;

use OTGS\Installer\AdminNotices\ToolsetConfig;
use OTGS\Installer\AdminNotices\WPMLConfig;
use OTGS\Installer\Collection;

class ApiConnection {
	const CONNECTION_ISSUES = 'connection-issues';

	/**
	 * @param \WP_Installer $installer
	 * @param array $initialNotices
	 *
	 * @return array
	 */
	public static function getCurrentNotices( \WP_Installer $installer, array $initialNotices ) {
		$config = $installer->getRepositories();

		$noticeTypes = [
			self::CONNECTION_ISSUES => [ApiConnection::class, 'shouldShowConnectionIssues'],
		];

		return collection::of( $noticeTypes )
		                 ->entities()
		                 ->reduce( Notice::addNoticesForType($installer, $config), Collection::of( $initialNotices ) )
		                 ->get();

	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowConnectionIssues( \WP_Installer $installer, array $nag ) {
		return $installer->shouldDisplayConnectionIssueMessage( $nag['repository_id'] );
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
					ApiConnection::CONNECTION_ISSUES => $wpmlPages,
				],
				'toolset' => [
					ApiConnection::CONNECTION_ISSUES => $toolsetPages,
				],
			],
		] );
	}

	public static function screens( array $screens ) {
		$config = [
			ApiConnection::CONNECTION_ISSUES => [ 'screens' => [ 'plugins', 'plugin-install' ] ],
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
					ApiConnection::CONNECTION_ISSUES => WPMLTexts::class . '::connectionIssues',
				],
				'toolset' => [
					ApiConnection::CONNECTION_ISSUES => ToolsetTexts::class . '::connectionIssues',
				],
			],
		] );
	}

	public static function dismissions( array $initialDismissions ) {
		return $initialDismissions;
	}
}
