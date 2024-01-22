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
class WPML_TM_AMS_ATE_Console_Section implements IWPML_TM_Admin_Section {
	const ATE_APP_ID         = 'eate_widget';
	const TAB_ORDER          = 10000;
	const CONTAINER_SELECTOR = '#ams-ate-console';
	const TAB_SELECTOR       = '.wpml-tabs .nav-tab.nav-tab-active.nav-tab-ate-ams';
	const SLUG               = 'ate-ams';

	/**
	 * An instance of \SitePress.
	 *
	 * @var SitePress The instance of \SitePress.
	 */
	private $sitepress;
	/**
	 * Instance of WPML_TM_ATE_AMS_Endpoints.
	 *
	 * @var WPML_TM_ATE_AMS_Endpoints
	 */
	private $endpoints;

	/**
	 * Instance of WPML_TM_ATE_Authentication.
	 *
	 * @var WPML_TM_ATE_Authentication
	 */
	private $auth;

	/**
	 * Instance of WPML_TM_AMS_API.
	 *
	 * @var WPML_TM_AMS_API
	 */
	private $ams_api;

	/**
	 * WPML_TM_AMS_ATE_Console_Section constructor.
	 *
	 * @param SitePress                  $sitepress The instance of \SitePress.
	 * @param WPML_TM_ATE_AMS_Endpoints  $endpoints The instance of WPML_TM_ATE_AMS_Endpoints.
	 * @param WPML_TM_ATE_Authentication $auth      The instance of WPML_TM_ATE_Authentication.
	 * @param WPML_TM_AMS_API            $ams_api   The instance of WPML_TM_AMS_API.
	 */
	public function __construct( SitePress $sitepress, WPML_TM_ATE_AMS_Endpoints $endpoints, WPML_TM_ATE_Authentication $auth, WPML_TM_AMS_API $ams_api ) {
		$this->sitepress = $sitepress;
		$this->endpoints = $endpoints;
		$this->auth      = $auth;
		$this->ams_api   = $ams_api;
	}

	/**
	 * Returns a value which will be used for sorting the sections.
	 *
	 * @return int
	 */
	public function get_order() {
		return self::TAB_ORDER;
	}

	/**
	 * Returns the unique slug of the sections which is used to build the URL for opening this section.
	 *
	 * @return string
	 */
	public function get_slug() {
		return self::SLUG;
	}

	/**
	 * Returns one or more capabilities required to display this section.
	 *
	 * @return string|array
	 */
	public function get_capabilities() {
		return [ User::CAP_MANAGE_TRANSLATIONS, User::CAP_ADMINISTRATOR, User::CAP_MANAGE_OPTIONS ];
	}

	/**
	 * Returns the caption to display in the section.
	 *
	 * @return string
	 */
	public function get_caption() {
		return __( 'Tools', 'wpml-translation-management' );
	}

	/**
	 * Returns the callback responsible for rendering the content of the section.
	 *
	 * @return callable
	 */
	public function get_callback() {
		return array( $this, 'render' );
	}

	/**
	 * Used to extend the logic for displaying/hiding the section.
	 *
	 * @return bool
	 */
	public function is_visible() {
		return true;
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
		if ( $this->is_ate_console_tab() ) {

			$script_url = \add_query_arg(
				[
					Widget::QUERY_VAR_ATE_WIDGET_SCRIPT => Widget::SCRIPT_NAME,
				],
				\trailingslashit( \site_url() )
			);

			\wp_enqueue_script( self::ATE_APP_ID, $script_url, [], ICL_SITEPRESS_VERSION, true );
		}
	}

	/**
	 * It returns true if the current page and tab are the ATE Console.
	 *
	 * @return bool
	 */
	private function is_ate_console_tab() {
		$sm   = Sanitize::stringProp('sm', $_GET );
		$page = Sanitize::stringProp( 'page', $_GET );

		return $sm && $page && self::SLUG === $sm && WPML_TM_FOLDER . '/menu/main.php' === $page;
	}

	/**
	 * It returns the list of all translatable post types.
	 *
	 * @return array
	 */
	private function get_post_types_data() {
		$translatable_types = $this->sitepress->get_translatable_documents( true );

		$data = [];
		if ( $translatable_types ) {
			foreach ( $translatable_types as $name => $post_type ) {
				$data[ esc_js( $name ) ] = [
					'labels'      => [
						'name'          => esc_js( $post_type->labels->name ),
						'singular_name' => esc_js( $post_type->labels->singular_name ),
					],
					'description' => esc_js( $post_type->description ),
				];
			}
		}

		return $data;
	}

	/**
	 * It returns the current user's language.
	 *
	 * @return string
	 */
	private function get_user_admin_language() {
		return $this->sitepress->get_user_admin_language( wp_get_current_user()->ID );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_widget_constructor() {
		$registration_data = $this->ams_api->get_registration_data();

		/** @var NoCreditPopup $noCreditPopup */
		$noCreditPopup = make( NoCreditPopup::class );

		$app_constructor = [
			'host'         => esc_js( $this->endpoints->get_base_url( WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ) ),
			'wpml_host'    => esc_js( get_site_url() ),
			'wpml_home'    => esc_js( get_home_url() ),
			'secret_key'   => esc_js( $registration_data['secret'] ),
			'shared_key'   => esc_js( $registration_data['shared'] ),
			'status'       => esc_js( $registration_data['status'] ),
			'tm_email'     => esc_js( wp_get_current_user()->user_email ),
			'website_uuid' => esc_js( $this->auth->get_site_id() ),
			'site_key'     => esc_js( apply_filters( 'otgs_installer_get_sitekey_wpml', null ) ),
			'dependencies' => [
				'sitepress-multilingual-cms' => [
					'version' => ICL_SITEPRESS_VERSION,
				],
			],
			'tab'          => self::TAB_SELECTOR,
			'container'    => self::CONTAINER_SELECTOR,
			'post_types'   => $this->get_post_types_data(),
			'ui_language'  => esc_js( $this->get_user_admin_language() ),
			'restNonce'    => wp_create_nonce( 'wp_rest' ),
			'authCookie'   => [
				'name'  => LOGGED_IN_COOKIE,
				'value' => $_COOKIE[ LOGGED_IN_COOKIE ],
			],
			'languages'    => $noCreditPopup->getLanguagesData(),
		];

		return $app_constructor;
	}

	/**
	 * @return string
	 */
	public function getWidgetScriptUrl() {
		return $this->endpoints->get_base_url( WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ) . '/mini_app/main.js';
	}
}
