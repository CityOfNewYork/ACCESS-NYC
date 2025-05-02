<?php

use WPML\API\Sanitize;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\ATE\Proxies\Widget;
use WPML\TM\ATE\NoCreditPopup;
use WPML\LIB\WP\User;
use function WPML\Container\make;

/**
 * It handles the TM section responsible for displaying the AMS/ATE console.
 *
 * This class takes care of the following:
 * - enqueuing the external script which holds the React APP
 * - adding the ID to the enqueued script (as it's required by the React APP)
 * - adding an inline script to initialize the React APP
 *
 * @author OnTheGo Systems
 */
class WPML_TM_AMS_Translation_Quality_Console_Section
	extends WPML_TM_AMS_Translation_Abstract_Console_Section
	implements IWPML_TM_Admin_Section
{
	const ATE_APP_ID         = 'eate_widget';
	const TAB_ORDER          = 500;
	const CONTAINER_SELECTOR = '#ams-ate-console';
	const TAB_SELECTOR       = '.wpml-tabs .nav-tab.nav-tab-active.nav-tab-ate-ams';
	const SLUG               = 'translation-quality';
	const SECTION_SLUG		= 'translation-quality';

	/**
	 * Returns the caption to display in the section.
	 *
	 * @return string
	 */
	public function get_caption() {
		return __( 'Translation Quality', 'sitepress' );
	}


	/**
	 * It returns true if the current page and tab are the ATE Console.
	 *
	 * @return bool
	 */
	protected function is_tab() {
		$sm   = Sanitize::stringProp('sm', $_GET );
		$page = Sanitize::stringProp( 'page', $_GET );

		return $sm && $page && self::SLUG === $sm && WPML_TM_FOLDER . '/menu/main.php' === $page;
	}
}
