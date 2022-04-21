<?php

namespace OTGS\Installer\AdminNotices\Notices;

use OTGS\Installer\Collection;
use function OTGS\Installer\FP\partial;

class Notice {
	/**
	 * @param \WP_Installer $installer
	 * @param array $config
	 *
	 * @return \Closure
	 */
	public static function addNoticesForType( $installer, $config ) {
		return function ( Collection $notices, array $data ) use ( $installer, $config ) {
			list( $type, $fn ) = $data;
			$addNotice  = partial( self::class . '::addNotice', $type );
			$shouldShow = partial( $fn, $installer );

			return $notices->mergeRecursive( Collection::of( $config )
			                                           ->filter( $shouldShow )
			                                           ->pluck( 'repository_id' )
			                                           ->reduce( $addNotice, [] ) );
		};
	}

	/**
	 * @param string $noticeId
	 * @param array $notices
	 * @param string $repoId
	 *
	 * @return array
	 */
	public static function addNotice( $noticeId, array $notices, $repoId ) {
		return array_merge_recursive( $notices, [ 'repo' => [ $repoId => [ $noticeId ] ] ] );
	}
}