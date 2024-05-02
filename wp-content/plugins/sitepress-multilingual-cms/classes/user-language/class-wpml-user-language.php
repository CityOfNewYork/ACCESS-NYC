<?php

use WPML\Element\API\Languages;
use WPML\FP\Maybe;
use WPML\FP\Relation;
use WPML\Language\Detection\CookieLanguage;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\User;
use WPML\LIB\WP\Option;
use WPML\UIPage;
use WPML\UrlHandling\WPLoginUrlConverter;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

/**
 * @package    wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language {
	/** @var  SitePress $sitepress */
	protected $sitepress;

	private $language_changes_history       = array();
	private $admin_language_changes_history = array();

	/**
	 * @var \wpdb|null
	 */
	private $wpdb;

	/**
	 * WPML_User_Language constructor.
	 *
	 * @param SitePress $sitepress
	 * @param wpdb|null $wpdb
	 */
	public function __construct( SitePress $sitepress, wpdb $wpdb = null ) {
		$this->sitepress = $sitepress;

		if ( ! $wpdb ) {
			global $wpdb;
		}
		$this->wpdb = $wpdb;

		$this->register_hooks();
	}

	public function register_hooks() {
		Hooks::onAction( 'wp_login', 10, 2 )
		     ->then( spreadArgs( [ $this, 'update_user_lang_from_login' ] ) );

		Hooks::onAction( 'init' )
		     ->then( [ $this, 'add_how_to_set_notice' ] );

		Hooks::onAction( 'wpml_user_profile_options' )
		     ->then( [ $this, 'show_ui_to_enable_login_translation' ] );

		add_action( 'wpml_switch_language_for_email', array( $this, 'switch_language_for_email_action' ), 10, 1 );
		add_action( 'wpml_restore_language_from_email', array( $this, 'restore_language_from_email_action' ), 10, 0 );
		add_action( 'profile_update', array( $this, 'sync_admin_user_language_action' ), 10, 1 );
		add_action( 'wpml_language_cookie_added', array( $this, 'update_user_lang_on_cookie_update' ) );

		if ( $this->is_editing_current_profile() || $this->is_editing_other_profile() ) {
			add_filter( 'get_available_languages', array( $this, 'intersect_wpml_wp_languages' ) );
		}

		register_activation_hook(
			WPML_PLUGIN_PATH . '/' . WPML_PLUGIN_FILE,
			[ $this, 'update_user_lang_on_site_setup' ]
		);
	}

	/**
	 * @param array $wp_languages
	 *
	 * @return array
	 */
	public function intersect_wpml_wp_languages( $wp_languages ) {
		$active_wpml_languages         = wp_list_pluck( $this->sitepress->get_active_languages(), 'default_locale' );
		$active_wpml_codes             = array_flip( $active_wpml_languages );
		$intersect_languages_by_locale = array_intersect( $active_wpml_languages, $wp_languages );
		$intersect_languages_by_code   = array_intersect( $active_wpml_codes, $wp_languages );

		return array_merge( $intersect_languages_by_code, $intersect_languages_by_locale );
	}

	/**
	 * @param string $email
	 */
	public function switch_language_for_email_action( $email ) {
		$this->switch_language_for_email( $email );
	}

	/**
	 * @param string $email
	 */
	private function switch_language_for_email( $email ) {
		$language = apply_filters( 'wpml_user_language', null, $email );

		if ( $language ) {
			$user_language  = $this->sitepress->get_current_language();
			$admin_language = $this->sitepress->get_admin_language();

			if ( $language !== $user_language || $language !== $admin_language ) {
				$this->language_changes_history[]       = $user_language;
				$this->admin_language_changes_history[] = $admin_language;

				$this->sitepress->switch_lang( $language, true );

				$this->sitepress->set_admin_language( $language );
			}
		}
	}

	public function restore_language_from_email_action() {
		$this->wpml_restore_language_from_email();
	}

	private function wpml_restore_language_from_email() {
		if ( count( $this->language_changes_history ) > 0 ) {
			$this->sitepress->switch_lang( array_pop( $this->language_changes_history ), true );
		}
		if ( count( $this->admin_language_changes_history ) > 0 ) {
			$this->sitepress->set_admin_language( array_pop( $this->admin_language_changes_history ) );
		}
	}

	/**
	 * @param int $user_id
	 */
	public function sync_admin_user_language_action( $user_id ) {
		if ( $this->user_needs_sync_admin_lang() ) {
			$this->sync_admin_user_language( $user_id );
		}
	}

	public function sync_default_admin_user_languages() {
		$sql_users   = 'SELECT user_id FROM ' . $this->wpdb->usermeta . ' WHERE meta_key = %s AND meta_value = %s';
		$query_users = $this->wpdb->prepare( $sql_users, array( 'locale', '' ) );
		$user_ids    = $this->wpdb->get_col( $query_users );

		if ( $user_ids ) {
			$language = $this->sitepress->get_default_language();

			$sql   = 'UPDATE ' . $this->wpdb->usermeta . ' SET meta_value = %s WHERE meta_key = %s and user_id IN (' . wpml_prepare_in( $user_ids ) . ')';
			$query = $this->wpdb->prepare( $sql, array( $language, 'icl_admin_language' ) );

			$this->wpdb->query( $query );
		}
	}

	/**
	 * @param int $user_id
	 */
	private function sync_admin_user_language( $user_id ) {
		$wp_language = get_user_meta( $user_id, 'locale', true );

		if ( $wp_language ) {
			$user_language = $this->select_language_code_from_locale( $wp_language );
		} else {
			$user_language = $this->sitepress->get_default_language();
		}
		update_user_meta( $user_id, 'icl_admin_language', $user_language );

		if ( $this->user_admin_language_for_edit( $user_id ) && $this->is_editing_current_profile() ) {
			$this->set_language_cookie( $user_language );
		}
	}

	/**
	 * @param string $wp_locale
	 *
	 * @return null|string
	 */
	private function select_language_code_from_locale( $wp_locale ) {
		$code = $this->sitepress->get_language_code_from_locale( $wp_locale );

		if ( ! $code ) {
			$guess_code   = strtolower( substr( $wp_locale, 0, 2 ) );
			$guess_locale = $this->sitepress->get_locale_from_language_code( $guess_code );

			if ( $guess_locale ) {
				$code = $guess_code;
			}
		}

		return $code;
	}

	private function user_needs_sync_admin_lang() {
		$wp_api = $this->sitepress->get_wp_api();

		return $wp_api->version_compare_naked( get_bloginfo( 'version' ), '4.7', '>=' );
	}

	private function set_language_cookie( $user_language ) {
		global $wpml_request_handler;

		if ( is_object( $wpml_request_handler ) ) {
			$wpml_request_handler->set_language_cookie( $user_language );
		}
	}

	/**
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	private function user_admin_language_for_edit( $user_id ) {
		return get_user_meta( $user_id, 'icl_admin_language_for_edit', true );
	}

	/**
	 * @param string $lang
	 */
	public function update_user_lang_on_cookie_update( $lang ) {
		$user_id = get_current_user_id();

		if ( $this->user_needs_sync_admin_lang() && $user_id && $this->user_admin_language_for_edit( $user_id ) ) {
			update_user_meta( $user_id, 'icl_admin_language', $lang );

			$wpLang = Maybe::of( $lang )
			               ->map( Languages::getLanguageDetails() )
			               ->map( Languages::getWPLocale() )
			               ->getOrElse( null );

			update_user_meta( $user_id, 'locale', $wpLang );
		}
	}

	private function is_editing_current_profile() {
		global $pagenow;

		return isset( $pagenow ) && 'profile.php' === $pagenow;
	}

	private function is_editing_other_profile() {
		global $pagenow;

		return isset( $pagenow ) && 'user-edit.php' === $pagenow;
	}

	public function update_user_lang_on_site_setup() {
		$current_user_id = get_current_user_id();
		$wp_user_lang    = get_user_meta( $current_user_id, 'locale', true );

		if ( ! $wp_user_lang ) {
			return;
		}

		$lang_code_from_locale = $this->select_language_code_from_locale( $wp_user_lang );
		$wpml_user_lang        = get_user_meta( $current_user_id, 'icl_admin_language', true );

		if ( $current_user_id && $lang_code_from_locale && ! $wpml_user_lang ) {
			update_user_meta( $current_user_id, 'icl_admin_language', $lang_code_from_locale );
		}
	}

	public function update_user_lang_from_login( $username, WP_User $user ) {
        $cookieName = 'wp-wpml_login_lang';
        Maybe::fromNullable( make( CookieLanguage::class, [ ':defaultLanguage' => '' ] )->get( $cookieName ) )
             ->map( [ $this->sitepress, 'get_locale_from_language_code' ] )
             ->reject( Relation::equals( User::getMetaSingle( $user->ID, 'locale' ) ) )
             ->map( User::updateMeta( $user->ID, 'locale' ) );

        $secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
        setcookie( $cookieName, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, $secure );
	}

	public function add_how_to_set_notice() {
		global $pagenow;
		$adminNotices = wpml_get_admin_notices();

		$noticeId    = self::class . 'how_to_set_notice';
		$noticeGroup = self::class;

		if (
			$pagenow !== 'profile.php'
			&& ! Option::getOr( WPLoginUrlConverter::SETTINGS_KEY, false )
		) {
			$notice = new WPML_Notice(
				$noticeId,
				self::getNotice(),
				$noticeGroup
			);
			$notice->set_css_class_types( [ 'info' ] );
			$notice->add_capability_check( [ 'manage_options' ] );
			$notice->set_dismissible( true );
			$notice->add_exclude_from_page( UIPage::TM_PAGE );
			$notice->add_user_restriction( User::getCurrentId() );
			$adminNotices->add_notice( $notice );
		} else {
			$adminNotices->remove_notice( $noticeGroup, $noticeId );
		}

	}

	public static function getNotice() {
		ob_start();
		?>
		<h2><?php esc_html_e( 'Do you want the WordPress admin to be in a different language?', 'sitepress' ); ?></h2>
		<p>
			<?php esc_html_e( 'WPML lets each user choose the admin language, unrelated of the language in which visitors will see the front-end of the site.', 'sitepress' ); ?>
			<br/>
			<br/>
			<?php
			/* translators: %s is replaced with the word 'profile' wrapped in a link */
			echo sprintf(
				__( 'Go to your %s to choose your admin language.', 'sitepress' ),
				'<a href="' . admin_url( 'profile.php' ) . '">' . __( 'profile', 'sitepress' ) . '</a>'
			);
			?>
		</p>
		<?php
		return ob_get_clean();
	}

	public function show_ui_to_enable_login_translation() {
		if ( current_user_can( 'manage_options' ) && ! WPLoginUrlConverter::isEnabled() ) {

			$settingsPage     = UIPage::getSettings() . '#ml-content-setup-sec-wp-login';
			$settingsPageLink = '<a href="' . $settingsPage . '">' . __( 'WPML->Settings', 'sitepress' ) . '</a>';
			// translators: %s link to WPML Settings page
			$message = esc_html__( 'WPML will include a language switcher on the WordPress login page. To change this, go to %s.', 'sitepress' );
			?>
			<tr class="user-language-wrap">
				<th><?php esc_html_e( 'Login Page:', 'sitepress' ); ?></th>
				<td>
					<?php wp_nonce_field( 'icl_login_page_translation_nonce', 'icl_login_page_translation_nonce' ); ?>
					<div id="wpml-login-translation">
						<p>
							<?php esc_html_e( 'Your site currently has language switching for the login page disabled.', 'sitepress' ); ?>
							<button type="button" class="button wpml-login-activate">
								<?php esc_html_e( 'Activate', 'sitepress' ); ?>
							</button>
							<span class="spinner" style="float: none"></span>
						</p>
					</div>
					<div id="wpml-login-translation-updated" style="display:none">
						<?php echo sprintf( $message, $settingsPageLink ); ?>
					</div>
					<script type="text/javascript">
						jQuery(function ($) {
							$('.wpml-login-activate').click(function () {
								$(this).prop('disabled', true);
								$(this).parent().find('.spinner').css('visibility', 'visible');
								$.ajax({
									url: ajaxurl,
									type: "POST",
									data: {
										icl_ajx_action: 'icl_login_page_translation',
										_icl_nonce: $('#icl_login_page_translation_nonce').val(),
										login_page_translation: 1
									},
									success: function (response) {
										$('#wpml-login-translation').hide();
										$('#wpml-login-translation-updated').css('display', 'block');
									}
								});
							});
						});
					</script>
				</td>
			</tr>
			<?php
		}
	}
}
