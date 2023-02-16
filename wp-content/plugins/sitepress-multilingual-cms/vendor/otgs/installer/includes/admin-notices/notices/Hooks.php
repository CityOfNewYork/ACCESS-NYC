<?php


namespace OTGS\Installer\AdminNotices\Notices;


use function OTGS\Installer\FP\partial;

class Hooks {
	public static function addHooks( $class, \WP_Installer $installer ) {
		add_filter( 'otgs_installer_admin_notices_config', [$class, 'config'] );
		add_filter( 'otgs_installer_admin_notices_texts', [$class, 'texts'] );
		add_filter( 'otgs_installer_admin_notices_dismissions', [$class, 'dismissions'] );
		add_filter(
			'otgs_installer_admin_notices',
			partial( [$class, 'getCurrentNotices'], $installer )
		);
	}
}