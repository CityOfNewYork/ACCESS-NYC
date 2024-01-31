<?php
namespace WPML\ATE\Proxies;

use WPML\API\Sanitize;
use WPML\LIB\WP\User;

class Widget implements \IWPML_Frontend_Action, \IWPML_DIC_Action {
	const QUERY_VAR_ATE_WIDGET_SCRIPT = 'wpml-app';
	const SCRIPT_NAME                 = 'ate-widget';

	public function add_hooks() {
		// The widget is called using a script tag with src /?wpml-app=ate-widget, which invokes a frontend call.
		// There were several issues with 3rd party plugins which block the previous solution using 'template_include'.
		// Better using 'template_redirect'. This also prevents loading any further unnecessary frontend stuff.
		add_action(
			'template_redirect',
			function() {
				$script = $this->get_script();
				if ( $script ) {
					include $script;
					die();
				}
			},
			-PHP_INT_MAX // Make sure to be the first. Some plugins using this hook also to prevent usual rendering.
		);
	}

	/**
	 * @return string|void
	 */
	public function get_script() {
		if ( ! User::canManageTranslations() ) {
			return false;
		}

		$app = Sanitize::stringProp( self::QUERY_VAR_ATE_WIDGET_SCRIPT, $_GET );

		if ( self::SCRIPT_NAME !== $app ) {
			return false;
		}

		$script = WPML_TM_PATH . '/res/js/' . $app . '.php';
		return file_exists( $script )
			? $script
			: false;
	}
}
