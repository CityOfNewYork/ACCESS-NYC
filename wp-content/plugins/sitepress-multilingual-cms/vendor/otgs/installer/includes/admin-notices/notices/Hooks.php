<?php


namespace OTGS\Installer\AdminNotices\Notices;


use function OTGS\Installer\FP\partial;

class Hooks {
	public static function addHooks( $class, \WP_Installer $installer ) {
		/** @phpstan-ignore-next-line  */
		add_filter( 'otgs_installer_admin_notices_config', [$class, 'config'] );
		/** @phpstan-ignore-next-line  */
		add_filter( 'otgs_installer_admin_notices_texts', [$class, 'texts'] );
		/** @phpstan-ignore-next-line  */
		add_filter( 'otgs_installer_admin_notices_dismissions', [$class, 'dismissions'] );
		/** @phpstan-ignore-next-line  */
		add_filter( 'otgs_installer_admin_notices', partial( [$class, 'getCurrentNotices'], $installer ) );
	}
}