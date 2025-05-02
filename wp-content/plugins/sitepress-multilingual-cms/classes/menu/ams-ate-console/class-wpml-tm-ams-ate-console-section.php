<?php

use WPML\API\Sanitize;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\ATE\Proxies\Widget;
use WPML\ATE\Proxies\Dashboard;
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
class WPML_TM_AMS_ATE_Console_Section extends WPML_TM_AMS_Translation_Abstract_Console_Section implements IWPML_TM_Admin_Section {
	const ATE_APP_ID         = 'eate_widget';
	const ATE_DASHBOARD_ID   = 'eate_dashboard';
	const TAB_ORDER          = 10000;
	const CONTAINER_SELECTOR = '#ams-ate-console';
	const TAB_SELECTOR       = '.wpml-tabs .nav-tab.nav-tab-active.nav-tab-ate-ams';
	const SLUG               = 'ate-ams';


	/**
	 * Returns the caption to display in the section.
	 *
	 * @return string
	 */
	public function get_caption() {
		return __( 'Translation Tools', 'sitepress' );
	}

	/**
	 * Outputs the content of the section.
	 */
	public function render() {
		$supportUrl  = 'https://wpml.org/forums/forum/english-support/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmltm';
		$supportLink = '<a target="_blank" rel="nofollow" href="' . esc_url( $supportUrl ) . '">'
		               . esc_html__( 'contact our support team', 'wpml-translation-management' )
		               . '</a>';


		?>
		<div id="ams-ate-console">
			<div class="notice inline notice-error" style="display:none; padding:20px">
				<?php echo sprintf(
				// translators: %s is a link with 'contact our support team'
					esc_html(
						__( 'There is a problem connecting to automatic translation. Please check your internet connection and try again in a few minutes. If you continue to see this message, please %s.', 'wpml-translation-management' )
					),
					$supportLink
				);
				?>
			</div>
			<span class="spinner is-active" style="float:left"></span>
		</div>
		<script type="text/javascript">
			setTimeout(function () {
				jQuery('#ams-ate-console .notice').show();
				jQuery("#ams-ate-console .spinner").removeClass('is-active');
			}, 20000);
		</script>
		<?php
	}

	/**
	 * This method is hooked to the `admin_enqueue_scripts` action.
	 *
	 * @param string $hook The current page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		// call parent class admin_enqueue_scripts method
		$this->admin_enqueue_tab_scripts();

		if (is_admin()) {
			// AMS dashboard script must load always
			$dashboard_script_url = \add_query_arg(
				[
					Dashboard::QUERY_VAR_ATE_WIDGET_SCRIPT => Dashboard::SCRIPT_NAME,
				],
				\trailingslashit(\site_url())
			);

			\wp_enqueue_script(self::ATE_DASHBOARD_ID, $dashboard_script_url, [], ICL_SITEPRESS_SCRIPT_VERSION, true);
		}
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
