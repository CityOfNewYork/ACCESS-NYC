<?php

namespace OTGS\Installer\Recommendations;

class Storage {
	const ADMIN_NOTICES_OPTION = 'otgs_installer_recommendations_admin_notices_v2';

	/**
	 * @param string $key
	 * @param array $data
	 */
	public static function save( $key, $data ) {
		$current                                   = get_option( self::ADMIN_NOTICES_OPTION, [] );
		$current[ $data['repository_id'] ][ $key ] = $data;
		update_option( self::ADMIN_NOTICES_OPTION, $current, 'no' );
	}

	public static function delete( $pluginSlug, $repositoryId ) {
		$current = get_option( self::ADMIN_NOTICES_OPTION, [] );
		unset( $current[ $repositoryId ][ $pluginSlug ] );
		update_option( self::ADMIN_NOTICES_OPTION, $current, 'no' );
	}

	/**
	 * @return array
	 */
	public static function getAll() {
		return get_option( self::ADMIN_NOTICES_OPTION, [] );
	}
}
