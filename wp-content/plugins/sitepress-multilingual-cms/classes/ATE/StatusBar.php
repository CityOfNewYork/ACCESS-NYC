<?php

namespace WPML\TM\ATE;

use WPML\BackgroundTask\BackgroundTask;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\User;
use function WPML\FP\spreadArgs;
use WPML\Setup\Option;

class StatusBar {

	/**
	 * @return void
	 */
	public static function add_hooks() {
		if ( User::canManageTranslations() ) {
			Hooks::onAction( 'admin_bar_menu', 999 )
			     ->then( spreadArgs( [ self::class, 'add' ] ) );
		}
	}

	public static function add( \WP_Admin_Bar $adminBar ) {
		$adminBar->add_node(
			[
				'parent' => false,
				'id'     => 'ate-status-bar',
				'title'  => '<i id="wpml-status-bar-icon" class="otgs-ico otgs-ico-wpml"></i>' .
					'<span id="wp-admin-bar-ate-status-bar-badge"></span>',
				'href'   => false,
				'meta'   => [ 'class' => 'wpml-status-bar-hidden' ]
			]
		);
		$adminBar->add_node(
			[
				'parent' => 'ate-status-bar',
				'id'     => 'ate-status-bar-content',
				'meta'   => [ 'html' => '<div id="wpml-ate-status-bar-content"></div>' ],
				'href'   => false,
			]
		);
	}
}
