<?php
/**
 * @package wpml-core
 */

use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\TM\API\Batch;
use WPML\TM\Jobs\Dispatch\BatchBuilder;
use WPML\TM\Jobs\Dispatch\Posts;
use WPML\TM\Jobs\Dispatch\Packages;
use WPML\TM\Jobs\Dispatch\Messages;
use WPML\TM\API\Basket;
use WPML\UIPage;
use function WPML\Container\make;
use function WPML\FP\invoke;
use WPML\Setup\Option;
use function WPML\FP\partialRight;

/**
 * Class TranslationManagement
 *
 * Use `wpml_load_core_tm` to get an instance
 *
 * @package wpml-core
 */
class TranslationManagement {

	const INIT_PRIORITY = 1500;

	const DUPLICATE_ELEMENT_ACTION = 2;
	const TRANSLATE_ELEMENT_ACTION = 1;

	/**
	 * @var WPML_Translator
	 */
	private $selected_translator;
	/**
	 * @var WPML_Translator
	 */
	private $current_translator;
	private $messages = array();
	public $settings;
	public $admin_texts_to_translate = array();
	private $comment_duplicator;

	/** @var WPML_Custom_Field_Setting_Factory $settings_factory */
	private $settings_factory;

	/** @var  WPML_Cache_Factory */
	private $cache_factory;

	/**
	 * Keep list of message ID suffixes.
	 *
	 * @access private
	 */
	private $message_ids = array( 'add_translator', 'edit_translator', 'remove_translator', 'save_notification_settings', 'cancel_jobs' );
	/**
	 * @var \WPML_Translation_Management_Filters_And_Actions
	 */
	private $filters_and_actions;

	/**
	 * @var \WPML_Cookie
	 */
	private $wpml_cookie;

	function __construct( WPML_Cookie $wpml_cookie = null ) {

		global $sitepress, $wpml_cache_factory;

		$this->selected_translator     = new WPML_Translator();
		$this->selected_translator->ID = 0;
		$this->current_translator      = new WPML_Translator();
		$this->current_translator->ID  = 0;
		$this->cache_factory           = $wpml_cache_factory;

		add_action( 'init', array( $this, 'init' ), self::INIT_PRIORITY );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 0 );
		add_action( 'delete_post', array( $this, 'delete_post_actions' ), 1, 1 ); // Calling *before* the SitePress actions.
		add_action( 'icl_ajx_custom_call', array( $this, 'ajax_calls' ), 10, 2 );
		add_action( 'wpml_tm_basket_add_message', array( $this, 'add_basket_message' ), 10, 3 );

		// 1. on Translation Management dashboard and jobs tabs
		// 2. on Translation Management dashboard tab (called without sm parameter as default page)
		// 3. Translations queue
		if ( ( isset( $_GET['sm'] ) && ( $_GET['sm'] == 'dashboard' || $_GET['sm'] == 'jobs' ) )
			 || ( isset( $_GET['page'] ) && preg_match( '@/menu/main\.php$@', $_GET['page'] ) && ! isset( $_GET['sm'] ) )
			 || ( isset( $_GET['page'] ) && preg_match( '@/menu/translations-queue\.php$@', $_GET['page'] ) )
		) {
			@session_start();
		}
		add_filter( 'icl_additional_translators', array( $this, 'icl_additional_translators' ), 99, 3 );

		add_action( 'wp_ajax_icl_tm_abort_translation', array( $this, 'abort_translation' ) );

		add_action( 'display_basket_notification', array( $this, 'display_basket_notification' ), 10, 1 );
		Fns::each(
			function( $type ) {
				add_action( "wpml_tm_send_{$type}_jobs", [ $this, 'send_jobs' ], 10, 3 ); },
			[ 'post', 'package', 'st-batch' ]
		);
		$this->init_comments_synchronization();
		add_action( 'wpml_loaded', array( $this, 'wpml_loaded_action' ) );

		/**
		 * @api
		 * @uses \TranslationManagement::get_translation_job_id
		 */
		add_filter( 'wpml_translation_job_id', array( $this, 'get_translation_job_id_filter' ), 10, 2 );

		$this->filters_and_actions = new WPML_Translation_Management_Filters_And_Actions( $this, $sitepress );

		$this->wpml_cookie = $wpml_cookie ?: new WPML_Cookie();
	}

	public function wpml_loaded_action() {
		$this->load_settings_if_required();
		if ( is_admin() ) {
			add_action( 'wpml_config', array( $this, 'wpml_config_action' ), 10, 1 );
		}
	}

	public function load_settings_if_required() {
		if ( ! $this->settings ) {
			$this->settings = apply_filters( 'wpml_setting', null, 'translation-management' );
		}
	}

	/**
	 * @param array $args      {
	 *
	 * @type string $section
	 * @type string $key
	 * @type mixed  $value     (when used as translation action: 0: do not translate, 1: copy, 2: translate)
	 * @type bool   $read_only Options. Default to true.
	 * }
	 */
	public function wpml_config_action( $args ) {
		if ( current_user_can( 'manage_options' ) ) {
			$this->update_section_translation_setting( $args );
		}
	}

	/**
	 * @return WPML_Custom_Field_Setting_Factory
	 */
	public function settings_factory() {
		$this->settings_factory = $this->settings_factory
			? $this->settings_factory
			: new WPML_Custom_Field_Setting_Factory( $this );

		return $this->settings_factory;
	}

	/**
	 * @param WP_User         $current_user
	 * @param WPML_Translator $current_translator
	 *
	 * @return WPML_Translator
	 */
	private function init_translator_language_pairs( WP_User $current_user, WPML_Translator $current_translator ) {
		global $wpdb;
		$current_translator_language_pairs  = get_user_meta( $current_user->ID, $wpdb->prefix . 'language_pairs', true );
		$current_translator->language_pairs = $this->sanitize_language_pairs( $current_translator_language_pairs );
		if ( ! count( $current_translator->language_pairs ) ) {
			$current_translator->language_pairs = array();
		}

		return $current_translator;
	}

	/**
	 * @param string $code
	 *
	 * @return bool
	 */
	private function is_valid_language_code_format( $code ) {
		return $code && is_string( $code ) && strlen( $code ) >= 2;
	}

	/**
	 * @param array $language_pairs
	 *
	 * @return array
	 */
	private function sanitize_language_pairs( $language_pairs ) {
		if ( ! $language_pairs || ! is_array( $language_pairs ) ) {
			$language_pairs = array();
		} else {
			$language_codes_from = array_keys( $language_pairs );
			foreach ( $language_codes_from as $code_from ) {
				$language_codes_to = array_keys( $language_pairs[ $code_from ] );

				foreach ( $language_codes_to as $code_to ) {
					if ( ! $this->is_valid_language_code_format( $code_to ) ) {
						unset( $language_pairs[ $code_from ][ $code_to ] );
					}
				}

				if ( ! $this->is_valid_language_code_format( $code_from ) || ! count( $language_pairs[ $code_from ] ) ) {
					unset( $language_pairs[ $code_from ] );
				}
			}
		}
		return $language_pairs;
	}

	/**
	 * @param array $args @see \TranslationManagement::wpml_config_action
	 */
	private function update_section_translation_setting( $args ) {
		$section   = $args['section'];
		$key       = $args['key'];
		$value     = $args['value'];
		$read_only = isset( $args['read_only'] ) ? $args['read_only'] : true;

		$section                        = preg_replace( '/-/', '_', $section );
		$config_section                 = $this->get_translation_setting_name( $section );
		$custom_config_readonly_section = $this->get_custom_readonly_translation_setting_name( $section );
		if ( isset( $this->settings[ $config_section ] ) ) {
			$this->settings[ $config_section ][ esc_sql( $key ) ] = esc_sql( $value );
			if ( ! isset( $this->settings[ $custom_config_readonly_section ] ) ) {
				$this->settings[ $custom_config_readonly_section ] = array();
			}
			if ( $read_only === true && ! in_array( $key, $this->settings[ $custom_config_readonly_section ] ) ) {
				$this->settings[ $custom_config_readonly_section ][] = esc_sql( $key );
			}
			$this->save_settings();
		}
	}

	public function init() {
		$this->init_comments_synchronization();
		$this->init_default_settings();

		WPML_Config::load_config();

		if ( is_admin() ) {
			if ( $GLOBALS['pagenow'] === 'edit.php' ) { // use standard WP admin notices
				add_action( 'admin_notices', array( $this, 'show_messages' ) );
			} else {                               // use custom WP admin notices
				add_action( 'icl_tm_messages', array( $this, 'show_messages' ) );
			}

			// Add duplicate identifier actions.
			$this->wpml_add_duplicate_check_actions();

			// default settings
			if ( empty( $this->settings['doc_translation_method'] ) || ! defined( 'WPML_TM_VERSION' ) ) {
				$this->settings['doc_translation_method'] = ICL_TM_TMETHOD_MANUAL;
			}
		}
	}

	public function get_settings() {
		$this->load_settings_if_required();
		return $this->settings;
	}

	public function wpml_add_duplicate_check_actions() {
		global $pagenow;
		if (
			'post.php' === $pagenow
			||
			( isset( $_POST['action'] ) && 'check_duplicate' === $_POST['action'] && DOING_AJAX )
		) {
			return new WPML_Translate_Independently();
		}
	}

	public function wp_loaded() {
		if ( isset( $_POST['icl_tm_action'] ) ) {
			$this->process_request( $_POST );
		} elseif ( isset( $_GET['icl_tm_action'] ) ) {
			$this->process_request( $_GET );
		}
	}

	public function admin_enqueue_scripts() {
		if ( ! defined( 'WPML_TM_FOLDER' ) ) {
			return;
		}

		if ( UIPage::isTMJobs( $_GET ) ) {
			wp_register_script( 'translation-remote-jobs', WPML_TM_URL . '/dist/js/jobs/app.js', array(), false, true );
			wp_enqueue_script( 'translation-remote-jobs' );
			wp_register_style( 'translation-remote-jobs', WPML_TM_URL . '/res/css/translation-jobs.css', array(), WPML_TM_VERSION );
			wp_enqueue_style( 'translation-remote-jobs' );
			wp_enqueue_script( OTGS_Assets_Handles::POPOVER_TOOLTIP );
			wp_enqueue_style( OTGS_Assets_Handles::POPOVER_TOOLTIP );
		} elseif ( UIPage::isTMBasket( $_GET ) ) {
			wp_register_style( 'translation-basket', WPML_TM_URL . '/res/css/translation-basket.css', array(), WPML_TM_VERSION );
			wp_enqueue_style( 'translation-basket' );
		} elseif ( UIPage::isTMTranslators( $_GET ) ) {
			wp_register_style( 'translation-translators', WPML_TM_URL . '/res/css/translation-translators.css', array( 'otgs-ico' ), WPML_TM_VERSION );
			wp_enqueue_style( 'translation-translators' );
		} elseif ( UIPage::isSettings( $_GET ) ) {
			wp_register_style( 'sitepress-translation-options', ICL_PLUGIN_URL . '/res/css/translation-options.css', array(), WPML_TM_VERSION );
			wp_enqueue_style( 'sitepress-translation-options' );
		} elseif ( UIPage::isTMDashboard( $_GET ) ) {
			wp_register_style( 'translation-dashboard', WPML_TM_URL . '/res/css/translation-dashboard.css', array(), WPML_TM_VERSION );
			wp_enqueue_style( 'translation-dashboard' );
			wp_register_style( 'translation-translators', WPML_TM_URL . '/res/css/translation-translators.css', array( 'otgs-ico' ), WPML_TM_VERSION );
			wp_enqueue_style( 'translation-translators' );
		}
	}

	public static function get_batch_name( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );
		if ( ! $batch_data || ! isset( $batch_data->batch_name ) ) {
			$batch_name = __( 'No Batch', 'sitepress' );
		} else {
			$batch_name = $batch_data->batch_name;
		}

		return $batch_name;
	}

	public static function get_batch_url( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );
		$batch_url  = '';
		if ( $batch_data && isset( $batch_data->tp_id ) && $batch_data->tp_id != 0 ) {
			$batch_url = OTG_TRANSLATION_PROXY_URL . "/projects/{$batch_data->tp_id}/external";
		}

		return $batch_url;
	}

	public static function get_batch_last_update( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );

		return $batch_data ? $batch_data->last_update : false;
	}

	public static function get_batch_tp_id( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );

		return $batch_data ? $batch_data->tp_id : false;
	}

	public static function get_batch_data( $batch_id ) {
		$cache_key   = $batch_id;
		$cache_group = 'get_batch_data';
		$cache_found = false;

		$batch_data = wp_cache_get( $cache_key, $cache_group, false, $cache_found );

		if ( $cache_found ) {
			return $batch_data;
		}

		global $wpdb;
		$batch_data_sql      = "SELECT * FROM {$wpdb->prefix}icl_translation_batches WHERE id=%d";
		$batch_data_prepared = $wpdb->prepare( $batch_data_sql, array( $batch_id ) );
		$batch_data          = $wpdb->get_row( $batch_data_prepared );

		wp_cache_set( $cache_key, $batch_data, $cache_group );

		return $batch_data;
	}

	function save_settings() {
		global $sitepress;

		$icl_settings['translation-management'] = $this->settings;
		$cpt_sync_option                        = $sitepress->get_setting( 'custom_posts_sync_option', array() );
		$cpt_sync_option                        = (bool) $cpt_sync_option === false ? $sitepress->get_setting( 'custom-types_sync_option', array() ) : $cpt_sync_option;
		$cpt_unlock_options                     = $sitepress->get_setting( 'custom_posts_unlocked_option', array() );

		if ( ! isset( $icl_settings['custom_posts_sync_option'] ) ) {
			$icl_settings['custom_posts_sync_option'] = array();
		}

		foreach ( $cpt_sync_option as $k => $v ) {
			$icl_settings['custom_posts_sync_option'][ $k ] = $v;
		}
		$icl_settings['translation-management']['custom-types_readonly_config'] = isset( $icl_settings['translation-management']['custom-types_readonly_config'] ) ? $icl_settings['translation-management']['custom-types_readonly_config'] : array();
		foreach ( $icl_settings['translation-management']['custom-types_readonly_config'] as $k => $v ) {
			if ( ! $this->is_unlocked_type( $k, $cpt_unlock_options ) ) {
				$icl_settings['custom_posts_sync_option'][ $k ] = $v;
			}
		}
		$sitepress->set_setting( 'translation-management', $icl_settings['translation-management'], true );
		$sitepress->set_setting( 'custom_posts_sync_option', $icl_settings['custom_posts_sync_option'], true );
		$this->settings = $sitepress->get_setting( 'translation-management' );
	}

	/**
	 * @return string[]
	 */
	public function initial_custom_field_translate_states() {
		global $wpdb;

		$this->initial_term_custom_field_translate_states();

		return $this->initial_translation_states( $wpdb->postmeta );
	}

	/**
	 * @return string[]
	 */
	public function initial_term_custom_field_translate_states() {
		global $wpdb;

		return ! empty( $wpdb->termmeta )
			? $this->initial_translation_states( $wpdb->termmeta )
			: array();
	}

	function process_request( $data ) {
		$action = $data['icl_tm_action'];
		$data   = stripslashes_deep( $data );
		switch ( $action ) {
			case 'edit':
				$this->selected_translator->ID = intval( $data['user_id'] );
				break;
			case 'dashboard_filter':
				$cookie_data = filter_var( http_build_query( $data['filter'] ), FILTER_SANITIZE_URL );
				$this->set_cookie( 'wp-translation_dashboard_filter', $cookie_data, time() + HOUR_IN_SECONDS );
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=dashboard', 302, 'WPML' );
				break;
			case 'reset_dashboard_filters':
				unset( $_COOKIE['wp-translation_dashboard_filter'] );
				$this->set_cookie( 'wp-translation_dashboard_filter', '', time() - HOUR_IN_SECONDS );
				break;
			case 'sort':
				$cookie_data = $this->get_cookie( 'wp-translation_dashboard_filter' );

				if ( isset( $data['sort_by'] ) ) {
					$cookie_data['sort_by'] = $data['sort_by'];
				}
				if ( isset( $data['sort_order'] ) ) {
					$cookie_data['sort_order'] = $data['sort_order'];
				}

				$cookie_data = filter_var( http_build_query( $cookie_data ), FILTER_SANITIZE_URL );
				$this->set_cookie( 'wp-translation_dashboard_filter', $cookie_data, time() + HOUR_IN_SECONDS );
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=dashboard', 302, 'WPML' );
				break;
			case 'add_jobs':
				if ( isset( $data['iclnonce'] ) && wp_verify_nonce( $data['iclnonce'], 'pro-translation-icl' ) ) {
					if ( Basket::shouldUse() ) {
						TranslationProxy_Basket::add_posts_to_basket( $data );
						do_action( 'wpml_tm_add_to_basket', $data );
					} elseif( Obj::prop( 'post', $data ) ) {
						$this->displayMessageThatJobsCreated();

						Posts::dispatch(
							Batch::class . '::sendPosts',
							new Messages(),
							BatchBuilder::buildPostsBatch(),
							$data
						);
					} elseif( Obj::prop( 'package', $data ) && Obj::prop('tr_action', $data) ) {
						$this->displayMessageThatJobsCreated();

						Packages::dispatch(
							Batch::class . '::sendPosts',
							new Messages(),
							BatchBuilder::buildPostsBatch(),
							$data
						);
					}
				}
				break;
			case 'ujobs_filter':
				$cookie_data                            = filter_var( http_build_query( $data['filter'] ), FILTER_SANITIZE_URL );
				$_COOKIE['wp-translation_ujobs_filter'] = $cookie_data;
				$this->set_cookie( 'wp-translation_ujobs_filter', $cookie_data, time() + HOUR_IN_SECONDS );
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php', 302, 'WPML' );
				break;
			case 'save_translation':
				if ( ! empty( $data['resign'] ) ) {
					$this->resign_translator( $data['job_id'] );
					if ( wp_safe_redirect( admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&resigned=' . $data['job_id'] ), 302, 'WPML' ) ) {
						exit;
					}
				} else {
					do_action( 'wpml_save_translation_data', $data );
				}
				break;
			case 'save_notification_settings':
				$this->icl_tm_save_notification_settings( $data );
				break;
			case 'cancel_jobs':
				$this->icl_tm_cancel_jobs( $data );
				break;
		}
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param int    $expiration
	 */
	private function set_cookie( $name, $value, $expiration ) {
		$this->wpml_cookie->set_cookie( $name, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * @param string $name
	 *
	 * @return array
	 */
	private function get_cookie( $name ) {
		$result = [];

		$cookie = new WPML_Cookie();
		$value  = $cookie->get_cookie( $name );

		parse_str( $value, $result );

		return $result;
	}

	function ajax_calls( $call, $data ) {
		global $wpdb, $sitepress;
		switch ( $call ) {
			case 'assign_translator':
				$translator_data        = TranslationProxy_Service::get_translator_data_from_wpml( $data['translator_id'] );
				$service_id             = $translator_data['translation_service'];
				$translator_id          = $translator_data['translator_id'];
				$assign_translation_job = $this->assign_translation_job( $data['job_id'], $translator_id, $service_id, $data['job_type'] );
				if ( $assign_translation_job ) {
					$translator_edit_link = '';
					if ( $translator_id ) {
						if ( $service_id == TranslationProxy::get_current_service_id() ) {
							$job = $this->get_translation_job( $data['job_id'] );
							/** @var WPML_Pro_Translation $ICL_Pro_Translation */
							global $ICL_Pro_Translation;
							$ICL_Pro_Translation->send_post( $job->original_doc_id, array( $job->language_code ), $translator_id, $data['job_id'] );
							$project = TranslationProxy::get_current_project();

							$translator_edit_link =
								TranslationProxy_Popup::get_link(
									$project->translator_contact_iframe_url( $translator_id ),
									array(
										'title'     => __( 'Contact the translator', 'sitepress' ),
										'unload_cb' => 'icl_thickbox_refresh',
									)
								)
								. esc_html( TranslationProxy_Translator::get_translator_name( $translator_id ) )
								. "</a> ($project->service->name)";
						} else {
							$translator_edit_link =
								'<a href="'
								. self::get_translator_edit_url( $data['translator_id'] )
								. '">'
								. esc_html( $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->users} WHERE ID=%d", $data['translator_id'] ) ) )
								. '</a>';
						}
					}
					echo wp_json_encode(
						array(
							'error'   => 0,
							'message' => $translator_edit_link,
							'status'  => self::status2text( ICL_TM_WAITING_FOR_TRANSLATOR ),
							'service' => $service_id,
						)
					);
				} else {
					echo wp_json_encode( array( 'error' => 1 ) );
				}
				break;
			case 'icl_cf_translation':
			case 'icl_tcf_translation':
				foreach (
					array(
						'cf'          => $call === 'icl_tcf_translation' ? WPML_TERM_META_SETTING_INDEX_PLURAL : WPML_POST_META_SETTING_INDEX_PLURAL,
						'cf_unlocked' => $call === 'icl_tcf_translation' ? WPML_TERM_META_UNLOCKED_SETTING_INDEX : WPML_POST_META_UNLOCKED_SETTING_INDEX,
					) as $field => $setting
				) {
					if ( ! empty( $data[ $field ] ) ) {
						$cft = array();
						foreach ( $data[ $field ] as $k => $v ) {
							$cft[ base64_decode( $k ) ] = $v;
						}
						if ( ! empty( $cft ) ) {
							if ( ! isset( $this->settings[ $setting ] ) ) {
								$this->settings[ $setting ] = array();
							}
							$this->settings[ $setting ] = array_merge( $this->settings[ $setting ], $cft );
							$this->save_settings();
							/**
							 * Fires after update of custom fields synchronisation preferences in WPML > Settings
							 */
							do_action( 'wpml_custom_fields_sync_option_updated', $cft );
						}
					}
				}
				echo '1|';
				break;
			case 'icl_doc_translation_method':
				if ( Obj::prop( 't_method', $data ) ) {
					$this->settings['doc_translation_method'] = Obj::prop( 't_method', $data );
					$sitepress->set_setting( 'doc_translation_method', $this->settings['doc_translation_method'] );
				}

				if ( isset( $data['tm_block_retranslating_terms'] ) ) {
					$sitepress->set_setting( 'tm_block_retranslating_terms', $data['tm_block_retranslating_terms'] );
				} else {
					$sitepress->set_setting( 'tm_block_retranslating_terms', '' );
				}
				if ( isset( $data['translation_memory'] ) ) {
					$sitepress->set_setting( 'translation_memory', $data['translation_memory'] );
				}

				$this->save_settings();
				echo '1|';
				break;
			case 'reset_duplication':
				$this->reset_duplicate_flag( $_POST['post_id'] );
				break;
			case 'set_duplication':
				$new_id = $this->set_duplicate( $_POST['wpml_original_post_id'], $_POST['post_lang'] );
				wp_send_json_success( array( 'id' => $new_id ) );
				break;
		}
	}

	/**
	 * @param string $element_type_full
	 *
	 * @return mixed
	 */
	public function get_element_prefix( $element_type_full ) {
		$element_type_parts = explode( '_', $element_type_full );
		$element_type       = $element_type_parts[0];

		return $element_type;
	}

	/**
	 * @param int $job_id
	 *
	 * @return mixed
	 */
	public function get_element_type_prefix_from_job_id( $job_id ) {
		$job = $this->get_translation_job( $job_id );

		if ( isset( $job->element_type_prefix ) ) {
			return $job->element_type_prefix;
		}

		return $job ? $this->get_element_type_prefix_from_job( $job ) : false;
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return mixed
	 */
	public function get_element_type_prefix_from_job( $job ) {
		if ( is_object( $job ) ) {
			$element_type        = $this->get_element_type( $job->trid );
			$element_type_prefix = $this->get_element_prefix( $element_type );
		} else {
			$element_type_prefix = false;
		}

		return $element_type_prefix;
	}

	/**
	 * Display admin notices.
	 */
	public function show_messages() {
		foreach ( $this->message_ids as $message_suffix ) {
			$message_id = 'icl_tm_message_' . $message_suffix;
			$message    = ICL_AdminNotifier::get_message( $message_id );
			if ( isset( $message['type'], $message['text'] ) ) {
				echo '<div class="' . esc_attr( $message['type'] ) . ' below-h2"><p>' . esc_html( $message['text'] ) . '</p></div>';
				ICL_AdminNotifier::remove_message( $message_id );
			}
		}
	}

	/* TRANSLATORS */

	/**
	 * @deprecated use `WPML_TM_Blog_Translators::get_blog_translators` instead
	 *
	 * @return bool
	 */
	public function has_translators() {
		if ( function_exists( 'wpml_tm_load_blog_translators' ) ) {
			return wpml_tm_load_blog_translators()->has_translators();
		}

		return false;
	}

	/**
	 * @deprecated use `WPML_TM_Blog_Translators::get_blog_translators` instead
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function get_blog_translators( $args = array() ) {
		$translators = array();

		if ( function_exists( 'wpml_tm_load_blog_translators' ) ) {
			$translators = wpml_tm_load_blog_translators()->get_blog_translators( $args );
		}

		return $translators;
	}

	/**
	 * @return WPML_Translator
	 */
	function get_selected_translator() {
		global $wpdb;
		if ( $this->selected_translator && $this->selected_translator->ID ) {
			$user                                      = new WP_User( $this->selected_translator->ID );
			$this->selected_translator->display_name   = $user->data->display_name;
			$this->selected_translator->user_login     = $user->data->user_login;
			$this->selected_translator->language_pairs = get_user_meta( $this->selected_translator->ID, $wpdb->prefix . 'language_pairs', true );
		} else {
			$this->selected_translator->ID = 0;
		}

		return $this->selected_translator;
	}

	/**
	 * @return WPML_Translator
	 */
	function get_current_translator() {
		$current_translator        = $this->current_translator;
		$current_translator_is_set = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;

		if ( ! $current_translator_is_set ) {
			$this->init_current_translator();
		}

		return $this->current_translator;
	}

	public static function get_translator_edit_url( $translator_id ) {
		$url = '';
		if ( ! empty( $translator_id ) ) {
			$url = 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&amp;sm=translators&icl_tm_action=edit&amp;user_id=' . $translator_id;
		}

		return $url;
	}

	/* HOOKS */

	function make_duplicates( $data ) {
		foreach ( $data['iclpost'] as $master_post_id ) {
			foreach ( $data['duplicate_to'] as $lang => $one ) {
				$this->make_duplicate( $master_post_id, $lang );
			}
		}
	}

	function make_duplicate( $master_post_id, $lang ) {
		global $sitepress;

		return $sitepress->make_duplicate( $master_post_id, $lang );
	}

	function make_duplicates_all( $master_post_id ) {
		global $sitepress;

		$master_post = get_post( $master_post_id );
		if ( $master_post->post_status == 'auto-draft' || $master_post->post_type == 'revision' ) {
			return;
		}

		$language_details_original = $sitepress->get_element_language_details( $master_post_id, 'post_' . $master_post->post_type );

		if ( ! $language_details_original ) {
			return;
		}

		$data['iclpost'] = array( $master_post_id );
		foreach ( $sitepress->get_active_languages() as $lang => $details ) {
			if ( $lang != $language_details_original->language_code ) {
				$data['duplicate_to'][ $lang ] = 1;
			}
		}

		$this->make_duplicates( $data );
	}

	function reset_duplicate_flag( $post_id ) {
		global $sitepress;

		$post = get_post( $post_id );

		$trid         = $sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );
		$translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type );

		foreach ( $translations as $tr ) {
			if ( $tr->element_id == $post_id ) {
				$this->update_translation_status(
					array(
						'translation_id' => $tr->translation_id,
						'status'         => ICL_TM_COMPLETE,
					)
				);
			}
		}

		delete_post_meta( $post_id, '_icl_lang_duplicate_of' );
	}

	function set_duplicate( $master_post_id, $post_lang ) {
		$new_id = 0;
		if ( $master_post_id && $post_lang ) {
			$new_id = $this->make_duplicate( $master_post_id, $post_lang );
		}

		return $new_id;
	}

	function duplication_delete_comment( $comment_id ) {
		global $wpdb;

		$original_comment = (bool) get_comment_meta( $comment_id, '_icl_duplicate_of', true ) === false;
		if ( $original_comment ) {
			$duplicates = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT comment_id
														   FROM {$wpdb->commentmeta}
														   WHERE meta_key='_icl_duplicate_of'
														   AND meta_value=%d",
					$comment_id
				)
			);
			foreach ( $duplicates as $dup ) {
				wp_delete_comment( $dup, true );
			}
		}
	}

	function duplication_edit_comment( $comment_id ) {
		global $wpdb;

		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID=%d", $comment_id ), ARRAY_A );
		unset( $comment['comment_ID'], $comment['comment_post_ID'] );

		$comment_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id=%d AND meta_key <> '_icl_duplicate_of'", $comment_id ) );

		$original_comment = get_comment_meta( $comment_id, '_icl_duplicate_of', true );
		if ( $original_comment ) {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment ) );
			$duplicates = array( $original_comment ) + array_diff( $duplicates, array( $comment_id ) );
		} else {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id ) );
		}

		if ( ! empty( $duplicates ) ) {
			foreach ( $duplicates as $dup ) {

				$wpdb->update( $wpdb->comments, $comment, array( 'comment_ID' => $dup ) );

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id=%d AND meta_key <> '_icl_duplicate_of'", $dup ) );

				if ( $comment_meta ) {
					foreach ( $comment_meta as $key => $value ) {
						wp_cache_delete( $dup, 'comment_meta' );
						update_comment_meta( $dup, $value->meta_key, $value->meta_value );
					}
				}
			}
		}
	}

	function duplication_status_comment( $comment_id, $comment_status ) {
		global $wpdb;

		static $_avoid_8_loop;

		if ( isset( $_avoid_8_loop ) ) {
			return;
		}
		$_avoid_8_loop = true;

		$original_comment = get_comment_meta( $comment_id, '_icl_duplicate_of', true );
		if ( $original_comment ) {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment ) );
			$duplicates = array( $original_comment ) + array_diff( $duplicates, array( $comment_id ) );
		} else {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id ) );
		}

		if ( ! empty( $duplicates ) ) {
			foreach ( $duplicates as $duplicate ) {
				wp_set_comment_status( $duplicate, $comment_status );
			}
		}

		unset( $_avoid_8_loop );
	}

	function duplication_insert_comment( $comment_id ) {
		global $wpdb, $sitepress;

		$duplicator = $this->get_comment_duplicator();

		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID=%d", $comment_id ), ARRAY_A );

		// loop duplicate posts, add new comment
		$post_id = $comment['comment_post_ID'];

		// if this is a duplicate post
		$duplicate_of = get_post_meta( $post_id, '_icl_lang_duplicate_of', true );
		if ( $duplicate_of ) {
			$post_duplicates = $sitepress->get_duplicates( $duplicate_of );
			$duplicator->move_to_original( $duplicate_of, $post_duplicates, $comment );
			$this->duplication_insert_comment( $comment_id );

			return;
		} else {
			$post_duplicates = $sitepress->get_duplicates( $post_id );
		}
		unset( $comment['comment_ID'], $comment['comment_post_ID'] );
		foreach ( $post_duplicates as $lang => $dup_id ) {
			$comment['comment_post_ID'] = $dup_id;

			if ( $comment['comment_parent'] ) {
				$translated_parent = $duplicator->get_correct_parent( $comment, $dup_id );
				if ( ! $translated_parent ) {
					$this->duplication_insert_comment( $comment['comment_parent'] );
					$translated_parent = $duplicator->get_correct_parent( $comment, $dup_id );
				}
				$comment['comment_parent'] = $translated_parent;
			}

			$duplicator->insert_duplicated_comment( $comment, $dup_id, $comment_id );
		}
	}

	private function get_comment_duplicator() {

		if ( ! $this->comment_duplicator ) {
			$this->comment_duplicator = new WPML_Comment_Duplication();
		}

		return $this->comment_duplicator;
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public function delete_post_actions( $post_id ) {
		global $wpdb;

		$post_type = $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id ) );

		if ( ! empty( $post_type ) ) {
			$trid_subquery = $wpdb->prepare(
				"SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s AND source_language_code IS NULL",
				$post_id,
				'post_' . $post_type
			);

			$translation_ids = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid = (" . $trid_subquery . ')'
			);

			if ( $translation_ids ) {
				$rids = $wpdb->get_col(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id IN (" . wpml_prepare_in( $translation_ids, '%d' ) . ')'
				);
				$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"DELETE FROM {$wpdb->prefix}icl_translation_status WHERE translation_id IN (" . wpml_prepare_in( $translation_ids, '%d' ) . ')'
				);

				if ( $rids ) {
					$job_ids = $wpdb->get_col(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						"SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ')'
					);
					$wpdb->query(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						"DELETE FROM {$wpdb->prefix}icl_translate_job WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ')'
					);
					if ( $job_ids ) {
						$wpdb->query(
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							"DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (" . wpml_prepare_in( $job_ids, '%d' ) . ')'
						);
					}
				}
			}
		}
	}

	/* TRANSLATIONS */

	/**
	 * calculate post md5
	 *
	 * @param object|int $post
	 *
	 * @return string
	 */
	function post_md5( $post ) {

		return apply_filters( 'wpml_tm_element_md5', $post );
	}

	function get_element_translation( $element_id, $language, $element_type = 'post_post' ) {
		global $wpdb, $sitepress;
		$trid        = $sitepress->get_element_trid( $element_id, $element_type );
		$translation = array();
		if ( $trid ) {
			$translation = $wpdb->get_row(
				$wpdb->prepare(
					"
				SELECT *
				FROM {$wpdb->prefix}icl_translations tr
				JOIN {$wpdb->prefix}icl_translation_status ts ON tr.translation_id = ts.translation_id
				WHERE tr.trid=%d AND tr.language_code= %s
			",
					$trid,
					$language
				)
			);
		}

		return $translation;
	}

	function get_element_translations( $element_id, $element_type = 'post_post', $service = false ) {
		global $wpdb, $sitepress;
		$trid         = $sitepress->get_element_trid( $element_id, $element_type );
		$translations = array();
		if ( $trid ) {
			$service      = $service ? $wpdb->prepare( ' AND translation_service = %s ', $service ) : '';
			$translations = $wpdb->get_results(
				$wpdb->prepare(
					"
				SELECT *
				FROM {$wpdb->prefix}icl_translations tr
				JOIN {$wpdb->prefix}icl_translation_status ts ON tr.translation_id = ts.translation_id
				WHERE tr.trid=%d {$service}
			",
					$trid
				)
			);
			foreach ( $translations as $k => $v ) {
				$translations[ $v->language_code ] = $v;
				unset( $translations[ $k ] );
			}
		}

		return $translations;
	}

	/**
	 * returns icon class according to status code
	 *
	 * @param int $status
	 * @param int $needs_update
	 * @param bool $needs_review
	 *
	 * @return string
	 */
	public function status2icon_class( $status, $needs_update = 0, $needs_review = false ) {
		if ( $needs_update ) {
			$icon_class = 'otgs-ico-needs-update';
		} elseif($needs_review) {
			$icon_class = 'otgs-ico-needs-review';
		} else {
			switch ( $status ) {
				case ICL_TM_NOT_TRANSLATED:
					$icon_class = 'otgs-ico-not-translated';
					break;
				case ICL_TM_WAITING_FOR_TRANSLATOR:
					$icon_class = 'otgs-ico-waiting';
					break;
				case ICL_TM_IN_PROGRESS:
				case ICL_TM_TRANSLATION_READY_TO_DOWNLOAD:
				case ICL_TM_ATE_NEEDS_RETRY:
					$icon_class = 'otgs-ico-in-progress';
					break;
				case ICL_TM_IN_BASKET:
					$icon_class = 'otgs-ico-basket';
					break;
				case ICL_TM_NEEDS_UPDATE:
					$icon_class = 'otgs-ico-needs-update';
					break;
				case ICL_TM_DUPLICATE:
					$icon_class = 'otgs-ico-duplicate';
					break;
				case ICL_TM_COMPLETE:
					$icon_class = 'otgs-ico-translated';
					break;
				default:
					$icon_class = 'otgs-ico-not-translated';
			}
		}

		return $icon_class;
	}

	public static function status2text( $status ) {
		switch ( $status ) {
			case ICL_TM_NOT_TRANSLATED:
				$text = __( 'Not translated', 'sitepress' );
				break;
			case ICL_TM_WAITING_FOR_TRANSLATOR:
				$text = __( 'Waiting for translator', 'sitepress' );
				break;
			case ICL_TM_IN_PROGRESS:
				$text = __( 'In progress', 'sitepress' );
				break;
			case ICL_TM_NEEDS_UPDATE:
				$text = __( 'Needs update', 'sitepress' );
				break;
			case ICL_TM_DUPLICATE:
				$text = __( 'Duplicate', 'sitepress' );
				break;
			case ICL_TM_COMPLETE:
				$text = __( 'Complete', 'sitepress' );
				break;
			case ICL_TM_TRANSLATION_READY_TO_DOWNLOAD:
				$text = __( 'Translation ready to download', 'sitepress' );
				break;
			case ICL_TM_ATE_NEEDS_RETRY:
				$text = __( 'In progress - needs retry', 'sitepress' );
				break;
			default:
				$text = '';
		}

		return $text;
	}

	public function decode_field_data( $data, $format ) {
		if ( $format == 'base64' ) {
			$data = base64_decode( $data );
		} elseif ( $format == 'csv_base64' ) {
			$exp = explode( ',', $data );
			foreach ( $exp as $k => $e ) {
				$exp[ $k ] = base64_decode( trim( $e, '"' ) );
			}
			$data = $exp;
		}

		return $data;
	}

	/**
	 * create translation package
	 *
	 * @param object|int $post
	 *
	 * @return array|false
	 */
	function create_translation_package( $post ) {
		return Maybe::fromNullable( make( 'WPML_Element_Translation_Package' ) )
			->map( invoke( 'create_translation_package' )->with( $post ) )
			->getOrElse( false );
	}

	function messages_by_type( $type ) {
		$messages = $this->messages;

		$result = false;
		foreach ( $messages as $message ) {
			if ( $type === false || ( ! empty( $message['type'] ) && $message['type'] == $type ) ) {
				$result[] = $message;
			}
		}

		return $result;
	}

	public function add_basket_message( $type, $message, $id = null ) {
		$message = array(
			'type' => $type,
			'text' => $message,
		);
		if ( $id ) {
			$message['id'] = $id;
		}

		$this->add_message( $message );
	}

	function add_message( $message ) {
		$this->messages[] = $message;
		$this->messages   = array_unique( $this->messages, SORT_REGULAR );
	}

	/**
	 * add/update icl_translation_status record
	 *
	 * @param array $data
	 * @param int   $rid
	 *
	 * @return array
	 */
	function update_translation_status( $data, $rid = null ) {
		global $wpdb;
		if ( ! isset( $data['translation_id'] ) ) {
			return array( false, false );
		}

		if ( ! $rid ) {
			$rid = $this->get_rid_from_translation_id( $data['translation_id'] );
		}

		$update = (bool) $rid;
		if ( true === $update ) {
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data, $data_where );
		} else {
			$wpdb->insert( $wpdb->prefix . 'icl_translation_status', $data );
			$rid = $wpdb->insert_id;
		}
		$data['rid'] = $rid;

		do_action( 'wpml_updated_translation_status', $data );

		return array( $rid, $update );
	}

	/**
	 * @param int $translation_id
	 *
	 * @return int
	 */
	private function get_rid_from_translation_id( $translation_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT rid
				FROM {$wpdb->prefix}icl_translation_status
				WHERE translation_id = %d",
				$translation_id
			)
		);
	}

	/* TRANSLATION JOBS */

	/**
	 * @param \WPML_TM_Translation_Batch $batch
	 * @param string $type
	 * @param int|null $sendFrom
	 *
	 * @return array
	 */
	function send_jobs( $batch, $type = 'post', $sendFrom = null ) {

		/**
		 * `\TranslationManagement::send_jobs` is in Core, and requires an instance of \WPML_TM_Translation_Batch
		 * which is defined in TM.
		 *
		 * We should move this code to TM instead.
		 *
		 * Until then, to prevent tests from failing:
		 *
		 * - We remove the type-hint from the method's signature
		 * - We compensate by using the following check
		 */
		if ( ! is_a( $batch, '\WPML_TM_Translation_Batch' ) ) {
			throw new InvalidArgumentException( '$batch must be an instance of \WPML_TM_Translation_Batch' );
		}

		global $sitepress;

		$job_ids    = array();
		$added_jobs = array();
		$batch_id   = TranslationProxy_Batch::update_translation_batch( $batch->get_basket_name() );

		/**
		 * Allows to filter the translation batch
		 *
		 * @since 4.3.0
		 *
		 * @param \WPML_TM_Translation_Batch $batch
		 */
		$batch = apply_filters( 'wpml_send_jobs_batch', $batch );

		foreach ( $batch->get_elements_by_type( $type ) as $element ) {
			$post = $this->get_post( $element->get_element_id(), $type );
			if ( ! $post ) {
				continue;
			}

			if ( $post instanceof \WP_Post ) {
				/**
				 * Registers strings coming from page builder shortcodes
				 *
				 * @param  \WP_Post
				 *
				 * @since 4.3.16
				 */
				do_action( 'wpml_pb_register_all_strings_for_translation', $post );
			}

			$element_type        = $type . '_' . $post->post_type;
			$post_trid           = $sitepress->get_element_trid( $element->get_element_id(), $element_type );
			$post_translations   = $sitepress->get_element_translations( $post_trid, $element_type );
			$md5                 = $this->post_md5( $post );
			$translation_package = $this->create_translation_package( $post );

			foreach ( $element->get_target_langs() as $lang => $action ) {

				if ( $action == self::DUPLICATE_ELEMENT_ACTION ) {
					// don't send documents that are in progress
					$current_translation_status = $this->get_element_translation( $element->get_element_id(), $lang, $element_type );
					if ( $current_translation_status && $current_translation_status->status == ICL_TM_IN_PROGRESS ) {
						continue;
					}

					$job_ids[] = $this->make_duplicate( $element->get_element_id(), $lang );
				} elseif ( $action == self::TRANSLATE_ELEMENT_ACTION ) {

					// INSERT DATA TO icl_translations
					if ( empty( $post_translations[ $lang ] ) ) {
						$translation_id = $sitepress->set_element_language_details( null, $element_type, $post_trid, $lang, $element->get_source_lang() );
					} else {
						$translation_id = $post_translations[ $lang ]->translation_id;
						$sitepress->set_element_language_details( $post_translations[ $lang ]->element_id, $element_type, $post_trid, $lang, $element->get_source_lang() );
					}

					$current_translation_status = $this->get_element_translation( $element->get_element_id(), $lang, $element_type );

					if ( $current_translation_status ) {
						if ( $current_translation_status->status == ICL_TM_IN_PROGRESS ) {
							$this->cancel_previous_job_if_in_progress( $translation_id );
						} else {
							$this->cancel_previous_job_if_still_waiting( $translation_id, $current_translation_status->status );
						}
					}

					$_status = ICL_TM_WAITING_FOR_TRANSLATOR;

					$translator       = $batch->get_translator( $lang );
					$translation_data = TranslationProxy_Service::get_translator_data_from_wpml( $translator );
					$translator_id    = $translation_data['translator_id'];

					$translation_service = $translation_data['translation_service'];

					// add translation_status record
					$data = array(
						'translation_id'      => $translation_id,
						'status'              => $_status,
						'translator_id'       => $translator_id,
						'needs_update'        => 0,
						'md5'                 => $md5,
						'translation_service' => $translation_service,
						'translation_package' => serialize( $translation_package ),
						'batch_id'            => $batch_id,
						'uuid'                => $this->get_uuid( $current_translation_status, $post ),
						'ts_status'           => null,
						'timestamp'           => date( 'Y-m-d H:i:s', time() ),
					);

					$backup_translation_status = $this->get_translation_status_data( $translation_id );
					$prevstate                 = $this->get_translation_prev_state( $backup_translation_status );

					if ( $prevstate ) {
						$data['_prevstate'] = serialize( $prevstate );
					}

					$rid = isset( $backup_translation_status['rid'] )
						? $backup_translation_status['rid'] : null;

					list( $rid ) = $this->update_translation_status( $data, $rid );
					$job_id      = $this->add_translation_job( $rid, $translator_id, $translation_package, $batch->get_batch_options() );
					wpml_tm_load_job_factory()->update_job_data( $job_id, array( 'editor' => WPML_TM_Editors::NONE ) );

					$job_ids[] = $job_id;

					if ( $translation_service !== 'local' ) {
						/** @global WPML_Pro_Translation $ICL_Pro_Translation */
						global $ICL_Pro_Translation;
						$tp_job_id = $ICL_Pro_Translation->send_post( $post, array( $lang ), $translator_id, $job_id );
						if ( ! $tp_job_id ) {
							$this->revert_job_when_tp_job_could_not_be_created( $job_ids, $rid, $data['translation_id'], $backup_translation_status );
						}

						// save associated TP JOB ID
						$this->update_translation_status(
							array(
								'translation_id' => $translation_id,
								'tp_id'          => $tp_job_id,
							),
							$rid
						);
					}

					$added_jobs[ $translation_service ][] = $job_id;
				}

				/**
				 * @param WPML_TM_Translation_Batch_Element $element
				 * @param mixed $post
				 * @since 4.4.0
				 */
				do_action( 'wpml_tm_added_translation_element', $element, $post );
			}
		}

		do_action( 'wpml_added_translation_jobs', $added_jobs, $sendFrom );

		icl_cache_clear();
		do_action( 'wpml_tm_empty_mail_queue' );

		return $job_ids;
	}

	private function revert_job_when_tp_job_could_not_be_created(
		$job_ids,
		$rid,
		$translator_id,
		$backup_translation_status
	) {
		/** @global WPML_Pro_Translation $ICL_Pro_Translation */
		global $wpdb, $ICL_Pro_Translation;

		$job_id = array_pop( $job_ids );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE rid=%d ORDER BY job_id DESC LIMIT 1", $rid ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id ) );
		if ( $backup_translation_status ) {
			$wpdb->update( "{$wpdb->prefix}icl_translation_status", $backup_translation_status, [ 'translation_id' => $translator_id ] );
		} else {
			$wpdb->delete( "{$wpdb->prefix}icl_translation_status", [ 'translation_id' => $translator_id ] );
		}
		foreach ( $ICL_Pro_Translation->errors as $error ) {
			if ( $error instanceof Exception ) {
				/** @var Exception $error */
				$message = [
					'type' => 'error',
					'text' => $error->getMessage(),
				];
				$this->add_message( $message );
			}
		}
	}

	/**
	 * @param stdClass|null        $current_translation_status
	 * @param WP_Post|WPML_Package $post
	 *
	 * @return string
	 */
	private function get_uuid( $current_translation_status, $post ) {
		if ( ! empty( $current_translation_status->uuid ) ) {
			return $current_translation_status->uuid;
		} else {
			return wpml_uuid( $post->ID, $post->post_type );
		}
	}

	private function get_translation_status_data( $translation_id ) {
		global $wpdb;
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
													FROM {$wpdb->prefix}icl_translation_status
													WHERE translation_id = %d",
				$translation_id
			),
			ARRAY_A
		);

		return isset( $data[0] ) ? $data[0] : array();
	}

	/**
	 * @param string $translation_id
	 * @param string $status
	 */
	private function cancel_previous_job_if_still_waiting( $translation_id, $status ) {
		if ( ICL_TM_WAITING_FOR_TRANSLATOR === (int) $status ) {
			$this->cancel_translation_request( $translation_id, false );
		}
	}

	private function cancel_previous_job_if_in_progress( $translation_id ) {
		global $wpdb;

		$sql = "
			SELECT j.job_id
			FROM {$wpdb->prefix}icl_translate_job j
			INNER JOIN {$wpdb->prefix}icl_translation_status ts ON ts.rid = j.rid
			WHERE ts.translation_id = %d AND ts.status = %s
			ORDER BY job_id DESC
		";

		$job_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $translation_id, ICL_TM_IN_PROGRESS ) );
		if ( ! $job_id ) {
			return;
		}

		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translated' => 1 ), array( 'job_id' => $job_id ) );
	}

	/**
	 * Adds a translation job record in icl_translate_job
	 *
	 * @param mixed                                     $rid
	 * @param mixed                                     $translator_id
	 * @param array<string,string|array<string,string>> $translation_package
	 * @param array                                     $batch_options
	 *
	 * @return bool|int
	 */
	function add_translation_job( $rid, $translator_id, $translation_package, $batch_options = array() ) {
		do_action( 'wpml_add_translation_job', $rid, $translator_id, $translation_package, $batch_options );

		return apply_filters( 'wpml_rid_to_untranslated_job_id', false, $rid );
	}

	function get_translation_jobs( $args = array() ) {

		return apply_filters( 'wpml_translation_jobs', array(), $args );
	}

	function get_translation_job_types( $args = array() ) {

		return apply_filters( 'wpml_translation_job_types', array(), $args );
	}

	/**
	 * Clean orphan jobs in posts
	 *
	 * @param array $posts
	 */
	function cleanup_translation_jobs_cart_posts( $posts ) {
		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post_id => $post_data ) {
			if ( ! get_post( $post_id ) ) {
				TranslationProxy_Basket::delete_item_from_basket( $post_id );
			}
		}
	}

	/**
	 * Incorporates posts in cart data with post title, post date, post notes,
	 * post type, post status
	 *
	 * @param array $posts
	 *
	 * @return boolean | array
	 */
	function get_translation_jobs_basket_posts( $posts ) {
		if ( empty( $posts ) ) {
			return false;
		}

		$this->cleanup_translation_jobs_cart_posts( $posts );

		global $sitepress;

		$posts_ids = array_keys( $posts );

		$args = array(
			'posts_per_page' => - 1,
			'include'        => $posts_ids,
			'post_type'      => get_post_types(),
			'post_status'    => get_post_stati(), // All post statuses
		);

		$new_posts = get_posts( $args );

		$final_posts = array();

		foreach ( $new_posts as $post_data ) {
			// set post_id
			$final_posts[ $post_data->ID ] = false;
			// set post_title
			$final_posts[ $post_data->ID ]['post_title'] = $post_data->post_title;
			// set post_date
			$final_posts[ $post_data->ID ]['post_date'] = $post_data->post_date;
			// set post_notes
			$final_posts[ $post_data->ID ]['post_notes'] = get_post_meta( $post_data->ID, '_icl_translator_note', true );

			// set post_type
			$final_posts[ $post_data->ID ]['post_type'] = $post_data->post_type;
			// set post_status
			$final_posts[ $post_data->ID ]['post_status'] = $post_data->post_status;
			// set from_lang
			$final_posts[ $post_data->ID ]['from_lang']        = $posts[ $post_data->ID ]['from_lang'];
			$final_posts[ $post_data->ID ]['from_lang_string'] = ucfirst( $sitepress->get_display_language_name( $posts[ $post_data->ID ]['from_lang'], $sitepress->get_admin_language() ) );
			// set to_langs
			$final_posts[ $post_data->ID ]['to_langs'] = $posts[ $post_data->ID ]['to_langs'];
			// set comma separated to_langs -> to_langs_string
			$language_names = array();
			foreach ( $final_posts[ $post_data->ID ]['to_langs'] as $language_code => $value ) {
				$language_names[] = ucfirst( $sitepress->get_display_language_name( $language_code, $sitepress->get_admin_language() ) );
			}
			$final_posts[ $post_data->ID ]['to_langs_string'] = implode( ', ', $language_names );
			$final_posts[ $post_data->ID ]['auto_added']      = isset( $posts[ $post_data->ID ]['auto_added'] ) && $posts[ $post_data->ID ]['auto_added'];
		}

		return $final_posts;
	}

	/**
	 * Incorporates strings in cart data
	 *
	 * @param array       $strings
	 * @param bool|string $source_language
	 *
	 * @return boolean | array
	 */
	function get_translation_jobs_basket_strings( $strings, $source_language = false ) {
		$final_strings = array();
		if ( class_exists( 'WPML_String_Translation' ) ) {
			global $sitepress;

			$source_language = $source_language ? $source_language : TranslationProxy_Basket::get_source_language();
			foreach ( $strings as $string_id => $data ) {
				if ( $source_language ) {
					// set post_id
					$final_strings[ $string_id ] = false;
					// set post_title
					$final_strings[ $string_id ]['post_title'] = icl_get_string_by_id( $string_id );
					// set post_type
					$final_strings[ $string_id ]['post_type'] = 'string';
					// set from_lang
					$final_strings[ $string_id ]['from_lang']        = $source_language;
					$final_strings[ $string_id ]['from_lang_string'] = ucfirst( $sitepress->get_display_language_name( $source_language, $sitepress->get_admin_language() ) );
					// set to_langs
					$final_strings[ $string_id ]['to_langs'] = $data['to_langs'];
					// set comma separated to_langs -> to_langs_string
					// set comma separated to_langs -> to_langs_string
					$language_names = array();
					foreach ( $final_strings[ $string_id ]['to_langs'] as $language_code => $value ) {
						$language_names[] = ucfirst( $sitepress->get_display_language_name( $language_code, $sitepress->get_admin_language() ) );
					}
					$final_strings[ $string_id ]['to_langs_string'] = implode( ', ', $language_names );
				}
			}
		}

		return $final_strings;
	}

	function get_translation_job( $job_id, $include_non_translatable_elements = false, $auto_assign = false, $revisions = 0 ) {
		return apply_filters( 'wpml_get_translation_job', $job_id, $include_non_translatable_elements, $revisions );
	}

	function get_translation_job_id_filter( $empty, $args ) {
		$trid          = $args['trid'];
		$language_code = $args['language_code'];

		return $this->get_translation_job_id( $trid, $language_code );
	}

	/**
	 * @param int $trid
	 *
	 * @return array
	 */
	private function get_translation_job_info( $trid ) {
		global $wpdb;

		$found    = false;
		$cache    = $this->cache_factory->get( 'TranslationManagement::get_translation_job_id' );
		$job_info = $cache->get( $trid, $found );
		if ( ! $found ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT tj.job_id, tj.editor, t.language_code FROM {$wpdb->prefix}icl_translate_job tj
					JOIN {$wpdb->prefix}icl_translation_status ts ON tj.rid = ts.rid
					JOIN {$wpdb->prefix}icl_translations t ON ts.translation_id = t.translation_id
					WHERE t.trid = %d
					ORDER BY tj.job_id DESC",
					$trid
				)
			);

			$job_info = array();
			foreach ( $results as $result ) {
				if ( ! isset( $job_info[ $result->language_code ] ) ) {
					$job_info[ $result->language_code ] = [
						'job_id' => $result->job_id,
						'editor' => $result->editor,
					];
				}
			}
			$cache->set( $trid, $job_info );
		}

		return $job_info;
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return int|null
	 */
	public function get_translation_job_id( $trid, $language_code ) {
		$job_info = $this->get_translation_job_info( $trid );

		return isset( $job_info[ $language_code ] ) ? $job_info[ $language_code ]['job_id'] : null;
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return string|null
	 */
	public function get_translation_job_editor( $trid, $language_code ) {
		$job_info = $this->get_translation_job_info( $trid );

		return isset( $job_info[ $language_code ] ) ? $job_info[ $language_code ]['editor'] : null;
	}

	function save_translation( $data ) {
		do_action( 'wpml_save_translation_data', $data );
	}

	/**
	 * Saves the contents a job's post to the job itself
	 *
	 * @param int $job_id
	 *
	 * @hook wpml_save_job_fields_from_post
	 * @deprecated since WPML 3.2.3 use the action hook wpml_save_job_fields_from_post
	 */
	function save_job_fields_from_post( $job_id ) {
		do_action( 'wpml_save_job_fields_from_post', $job_id );
	}

	function mark_job_done( $job_id ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translated' => 1 ), array( 'job_id' => $job_id ) );
		$wpdb->update( $wpdb->prefix . 'icl_translate', array( 'field_finished' => 1 ), array( 'job_id' => $job_id ) );
		do_action( 'wpml_tm_empty_mail_queue' );
	}

	function resign_translator( $job_id, $skip_notification = false ) {
		global $wpdb;
		list( $translator_id, $rid ) = $wpdb->get_row( $wpdb->prepare( "SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ), ARRAY_N );
		if ( ! $skip_notification && ! empty( $translator_id ) && $this->settings['notification']['resigned'] != ICL_TM_NOTIFICATION_NONE && $job_id ) {
			do_action( 'wpml_tm_resign_job_notification', $translator_id, $job_id );
		}
		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translator_id' => 0 ), array( 'job_id' => $job_id ) );
		$wpdb->update(
			$wpdb->prefix . 'icl_translation_status',
			array(
				'translator_id' => 0,
				'status'        => ICL_TM_WAITING_FOR_TRANSLATOR,
			),
			array( 'rid' => $rid )
		);
	}

	/**
	 * Resign the given translator from all unfinished translation jobs.
	 *
	 * @param WP_User $translator
	 */
	public function resign_translator_from_unfinished_jobs( WP_User $translator ) {
		global $wpdb;

		$unfinished_job_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT job_id 
				FROM {$wpdb->prefix}icl_translate_job 
				WHERE translator_id = %d AND translated = 0",
				$translator->ID
			)
		);

		$remove_job_without_notification = partialRight( [ $this, 'resign_translator' ], true );

		// Very unexpected to have a long list here, so it's fine deleting jobs one by one
		// instead of introducing new logic for resign.
		array_map( $remove_job_without_notification, $unfinished_job_ids );
	}

	function remove_translation_job( $job_id, $new_translation_status = ICL_TM_WAITING_FOR_TRANSLATOR, $new_translator_id = 0 ) {
		global $wpdb;

		$error = false;

		list( $prev_translator_id, $rid ) = $wpdb->get_row( $wpdb->prepare( "SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ), ARRAY_N );

		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translator_id' => $new_translator_id ), array( 'job_id' => $job_id ) );
		$wpdb->update(
			$wpdb->prefix . 'icl_translate',
			array(
				'field_data_translated' => '',
				'field_finished'        => 0,
			),
			array( 'job_id' => $job_id )
		);

		if ( $rid ) {
			$data       = array(
				'status'        => $new_translation_status,
				'translator_id' => $new_translator_id,
			);
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data, $data_where );

			if ( $this->settings['notification']['resigned'] == ICL_TM_NOTIFICATION_IMMEDIATELY && ! empty( $prev_translator_id ) ) {
				do_action( 'wpml_tm_remove_job_notification', $prev_translator_id, $job_id );
			}
		} else {
			$error = sprintf( __( 'Translation entry not found for: %d', 'sitepress' ), $job_id );
		}

		return $error;
	}

	function abort_translation() {
		$job_id  = $_POST['job_id'];
		$message = '';

		$error = $this->remove_translation_job( $job_id, ICL_TM_WAITING_FOR_TRANSLATOR, 0 );
		if ( ! $error ) {
			$message = __( 'Job removed', 'sitepress' );
		}

		echo wp_json_encode(
			array(
				'message' => $message,
				'error'   => $error,
			)
		);
		exit;
	}

	// $translation_id - int or array
	function cancel_translation_request( $translation_id, $remove_translation_record = true ) {
		global $wpdb, $WPML_String_Translation;

		if ( is_array( $translation_id ) ) {
			foreach ( $translation_id as $id ) {
				$this->cancel_translation_request( $id );
			}
		} else {

			if ( $WPML_String_Translation && wpml_mb_strpos( $translation_id, 'string|' ) === 0 ) {
				// string translations get handled in wpml-string-translation
				// first remove the "string|" prefix
				$id = substr( $translation_id, 7 );
				// then send it to the respective function in wpml-string-translation
				$WPML_String_Translation->cancel_local_translation( $id );

				return;
			}

			list( $rid, $translator_id ) = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT rid, translator_id
                     FROM {$wpdb->prefix}icl_translation_status
                     WHERE translation_id=%d
                       AND ( status = %d OR status = %d )",
					$translation_id,
					ICL_TM_WAITING_FOR_TRANSLATOR,
					ICL_TM_IN_PROGRESS
				),
				ARRAY_N
			);
			if ( ! $rid ) {
				return;
			}
			$job_id = $wpdb->get_var( $wpdb->prepare( "SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d AND revision IS NULL ", $rid ) );

			if ( isset( $this->settings['notification']['resigned'] )
			     && $this->settings['notification']['resigned'] == ICL_TM_NOTIFICATION_IMMEDIATELY && ! empty( $translator_id ) ) {
				do_action( 'wpml_tm_remove_job_notification', $translator_id, $job_id );
			}

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id ) );

			$max_job_id = \WPML\TM\API\Job\Map::fromRid( $rid );
			if ( $max_job_id ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE job_id=%d", $max_job_id ) );
				$previous_state = $wpdb->get_var( $wpdb->prepare( "SELECT _prevstate FROM {$wpdb->prefix}icl_translation_status WHERE translation_id = %d", $translation_id ) );
				if ( ! empty( $previous_state ) ) {
					$previous_state = unserialize( $previous_state );
					$arr_data       = array(
						'status'              => $previous_state['status'],
						'translator_id'       => $previous_state['translator_id'],
						'needs_update'        => $previous_state['needs_update'],
						'md5'                 => $previous_state['md5'],
						'translation_service' => $previous_state['translation_service'],
						'translation_package' => $previous_state['translation_package'],
						'timestamp'           => $previous_state['timestamp'],
						'links_fixed'         => $previous_state['links_fixed'],
					);
					$data_where     = array( 'translation_id' => $translation_id );
					$wpdb->update( $wpdb->prefix . 'icl_translation_status', $arr_data, $data_where );
				}
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id ) );
			}

			// delete record from icl_translations if element_id is null
			if ( $remove_translation_record ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d AND element_id IS NULL", $translation_id ) );
			}

			icl_cache_clear();
		}
	}

	function render_option_writes( $name, $value, $key = '' ) {
		if ( ! defined( 'WPML_ST_FOLDER' ) ) {
			return;
		}
		// Cache the previous option, when called recursively
		static $option = false;

		if ( ! $key ) {
			$option = maybe_unserialize( get_option( $name ) );
			if ( is_object( $option ) ) {
				$option = (array) $option;
			}
		}

		$admin_option_names = get_option( '_icl_admin_option_names' );

		// determine theme/plugin name (string context)
		$es_context = '';

		$context = '';
		$slug    = '';
		foreach ( $admin_option_names as $context => $element ) {
			$found = false;
			foreach ( (array) $element as $slug => $options ) {
				$found = false;
				foreach ( (array) $options as $option_key => $option_value ) {
					$found      = false;
					$es_context = '';
					if ( $option_key == $name ) {
						if ( is_scalar( $option_value ) ) {
							$es_context = 'admin_texts_' . $context . '_' . $slug;
							$found      = true;
						} elseif ( is_array( $option_value ) && is_array( $value ) && ( $option_value == $value ) ) {
							$es_context = 'admin_texts_' . $context . '_' . $slug;
							$found      = true;
						}
					}
					if ( $found ) {
						break;
					}
				}
				if ( $found ) {
					break;
				}
			}
			if ( $found ) {
				break;
			}
		}

		echo '<ul class="icl_tm_admin_options">';
		echo '<li>';

		$context_html = '';
		if ( ! $key ) {
			$context_html = '[' . esc_html( $context ) . ': ' . esc_html( $slug ) . '] ';
		}

		if ( is_scalar( $value ) ) {
			preg_match_all( '#\[([^\]]+)\]#', $key, $matches );

			if ( count( $matches[1] ) > 1 ) {
				$o_value = $option;
				for ( $i = 1; $i < count( $matches[1] ); $i++ ) {
					$o_value = $o_value[ $matches[1][ $i ] ];
				}
				$o_value   = $o_value[ $name ];
				$edit_link = '';
			} else {
				if ( is_scalar( $option ) ) {
					$o_value = $option;
				} elseif ( isset( $option[ $name ] ) ) {
					$o_value = $option[ $name ];
				} else {
					$o_value = '';
				}

				if ( ! $key ) {
					if ( icl_st_is_registered_string( $es_context, $name ) ) {
						$edit_link = '[<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . esc_html( $es_context ) ) . '">' . esc_html__( 'translate', 'sitepress' ) . '</a>]';
					} else {
						$edit_link = '<div class="updated below-h2">' . esc_html__( 'string not registered', 'sitepress' ) . '</div>';
					}
				} else {
					$edit_link = '';
				}
			}

			if ( false !== strpos( $name, '*' ) ) {
				$o_value = '<span style="color:#bbb">{{ ' . esc_html__( 'Multiple options', 'sitepress' ) . ' }}</span>';
			} else {
				$o_value = esc_html( $o_value );
				if ( strlen( $o_value ) > 200 ) {
					$o_value = substr( $o_value, 0, 200 ) . ' ...';
				}
			}
			echo $context_html . esc_html( $name ) . ': <i>' . $o_value . '</i> ' . $edit_link;
		} else {
			$edit_link = '[<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . esc_html( $es_context ) ) . '">' . esc_html__( 'translate', 'sitepress' ) . '</a>]';
			echo '<strong>' . $context_html . $name . '</strong> ' . $edit_link;
			if ( ! icl_st_is_registered_string( $es_context, $name ) ) {
				$notice = '<div class="updated below-h2">' . esc_html__( 'some strings might be not registered', 'sitepress' ) . '</div>';
				echo $notice;
			}

			foreach ( (array) $value as $o_key => $o_value ) {
				$this->render_option_writes( $o_key, $o_value, $o_key . '[' . $name . ']' );
			}

			// Reset cached data
			$option = false;
		}
		echo '</li>';
		echo '</ul>';
	}

	/**
	 * @param array $info
	 *
	 * @deprecated @since 3.2 Use TranslationProxy::get_current_service_info instead
	 * @return array
	 */
	public static function current_service_info( $info = array() ) {
		return TranslationProxy::get_current_service_info( $info );
	}

	// set slug according to user preference
	static function set_page_url( $post_id ) {

		global $wpdb;

		if ( wpml_get_setting_filter( false, 'translated_document_page_url' ) === 'copy-encoded' ) {

			$post            = $wpdb->get_row( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id ) );
			$translation_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $post_id, 'post_' . $post->post_type ) );

			$encode_url = $wpdb->get_var( $wpdb->prepare( "SELECT encode_url FROM {$wpdb->prefix}icl_languages WHERE code=%s", $translation_row->language_code ) );
			if ( $encode_url ) {

				$trid               = $translation_row->trid;
				$original_post_id   = $wpdb->get_var( $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $trid ) );
				$post_name_original = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID = %d", $original_post_id ) );

				$post_name_to_be = $post_name_original;
				$incr            = 1;
				do {
					$taken = $wpdb->get_var(
						$wpdb->prepare(
							"
						SELECT ID FROM {$wpdb->posts} p
						JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id
						WHERE ID <> %d AND t.element_type = %s AND t.language_code = %s AND p.post_name = %s
						",
							$post_id,
							'post_' . $post->post_type,
							$translation_row->language_code,
							$post_name_to_be
						)
					);
					if ( $taken ) {
						$incr ++;
						$post_name_to_be = $post_name_original . '-' . $incr;
					} else {
						$taken = false;
					}
				} while ( $taken == true );
				$post_to_update = new WPML_WP_Post( $wpdb, $post_id );
				$post_to_update->update( array( 'post_name' => $post_name_to_be ), true );
			}
		}
	}

	/**
	 * @param array<string,mixed> $postarr
	 * @param string              $lang
	 *
	 * @return int|WP_Error
	 * @deprecated since 4.2.8 Use directly `wpml_get_create_post_helper()` instead.
	 *
	 */
	public function icl_insert_post( $postarr, $lang ) {
		$create_post_helper = wpml_get_create_post_helper();

		return $create_post_helper->insert_post( $postarr, $lang );
	}

	/**
	 * Add missing language to posts
	 *
	 * @param array $post_types
	 */
	private function add_missing_language_to_posts( $post_types ) {
		global $wpdb;

		// This will be improved when it will be possible to pass an array to the IN clause
		$posts_prepared = "SELECT ID, post_type, post_status FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "', '", esc_sql( $post_types ) ) . "')";
		$posts          = $wpdb->get_results( $posts_prepared );
		if ( $posts ) {
			foreach ( $posts as $post ) {
				$this->add_missing_language_to_post( $post );
			}
		}
	}

	/**
	 * Add missing language to a given post
	 *
	 * @param WP_Post $post
	 */
	private function add_missing_language_to_post( $post ) {
		global $sitepress, $wpdb;

		$query_prepared = $wpdb->prepare( "SELECT translation_id, language_code FROM {$wpdb->prefix}icl_translations WHERE element_type=%s AND element_id=%d", array( 'post_' . $post->post_type, $post->ID ) );
		$query_results  = $wpdb->get_row( $query_prepared );

		// if translation exists
		if ( ! is_null( $query_results ) ) {
			$translation_id = $query_results->translation_id;
			$language_code  = $query_results->language_code;
		} else {
			$translation_id = null;
			$language_code  = null;
		}

		$urls             = $sitepress->get_setting( 'urls' );
		$is_root_page     = $urls && isset( $urls['root_page'] ) && $urls['root_page'] == $post->ID;
		$default_language = $sitepress->get_default_language();

		if ( ! $translation_id && ! $is_root_page && ! in_array( $post->post_status, array( 'auto-draft' ) ) ) {
			$sitepress->set_element_language_details( $post->ID, 'post_' . $post->post_type, null, $default_language );
		} elseif ( $translation_id && $is_root_page ) {
			$trid = $sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
			if ( $trid ) {
				$sitepress->delete_element_translation( $trid, 'post_' . $post->post_type );
			}
		} elseif ( $translation_id && ! $language_code && $default_language ) {
			$where = array( 'translation_id' => $translation_id );
			$data  = array( 'language_code' => $default_language );
			$wpdb->update( $wpdb->prefix . 'icl_translations', $data, $where );

			do_action(
				'wpml_translation_update',
				array(
					'type'           => 'update',
					'element_id'     => $post->ID,
					'element_type'   => 'post_' . $post->post_type,
					'translation_id' => $translation_id,
					'context'        => 'post',
				)
			);
		}
	}

	/**
	 * Add missing language to taxonomies
	 *
	 * @param array $post_types
	 */
	private function add_missing_language_to_taxonomies( $post_types ) {
		global $sitepress, $wpdb;
		$taxonomy_types = array();
		foreach ( $post_types as $post_type ) {
			$taxonomy_types = array_merge( $sitepress->get_translatable_taxonomies( true, $post_type ), $taxonomy_types );
		}
		$taxonomy_types = array_unique( $taxonomy_types );
		$taxonomies     = $wpdb->get_results( "SELECT taxonomy, term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN (" . wpml_prepare_in( $taxonomy_types ) . ')' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				$this->add_missing_language_to_taxonomy( $taxonomy );
			}
		}
	}

	/**
	 * Add missing language to a given taxonomy
	 *
	 * @param OBJECT $taxonomy
	 */
	private function add_missing_language_to_taxonomy( $taxonomy ) {
		global $sitepress, $wpdb;
		$tid_prepared = $wpdb->prepare( "SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type=%s AND element_id=%d", 'tax_' . $taxonomy->taxonomy, $taxonomy->term_taxonomy_id );
		$tid          = $wpdb->get_var( $tid_prepared );
		if ( ! $tid ) {
			$sitepress->set_element_language_details( $taxonomy->term_taxonomy_id, 'tax_' . $taxonomy->taxonomy, null, $sitepress->get_default_language() );
		}
	}

	/**
	 * Add missing language information to entities that don't have this
	 * information configured.
	 */
	public function add_missing_language_information() {
		global $sitepress;
		$translatable_documents = array_keys( $sitepress->get_translatable_documents( false ) );
		if ( $translatable_documents ) {
			$this->add_missing_language_to_posts( $translatable_documents );
			$this->add_missing_language_to_taxonomies( $translatable_documents );
		}
	}

	public static function include_underscore_templates( $name ) {
		$dir_str = WPML_TM_PATH . '/res/js/' . $name . '/templates/';
		$dir     = opendir( $dir_str );
		while ( ( $currentFile = readdir( $dir ) ) !== false ) {
			if ( $currentFile == '.' || $currentFile == '..' || $currentFile[0] == '.' ) {
				continue;
			}

			/** @noinspection PhpIncludeInspection */
			include $dir_str . $currentFile;
		}
		closedir( $dir );
	}

	public static function get_job_status_string( $status_id, $needs_update = false ) {
		$job_status_text = self::status2text( $status_id );
		if ( $needs_update ) {
			$job_status_text .= __( ' - (needs update)', 'sitepress' );
		}

		return $job_status_text;
	}

	function display_basket_notification( $position ) {
		if ( class_exists( 'ICL_AdminNotifier' ) && class_exists( 'TranslationProxy_Basket' ) ) {
			$positions = TranslationProxy_Basket::get_basket_notification_positions();
			if ( isset( $positions[ $position ] ) ) {
				ICL_AdminNotifier::display_messages( 'translation-basket-notification' );
			}
		}
	}

	public function get_element_type( $trid ) {
		global $wpdb;
		$element_type_query   = "SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid=%d LIMIT 0,1";
		$element_type_prepare = $wpdb->prepare( $element_type_query, $trid );

		return $wpdb->get_var( $element_type_prepare );
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public function is_external_type( $type ) {
		return apply_filters( 'wpml_is_external', false, $type );
	}

	/**
	 * @param int    $post_id
	 * @param string $element_type_prefix
	 *
	 * @return mixed|null|void|WP_Post
	 */
	public function get_post( $post_id, $element_type_prefix ) {
		$item = null;
		if ( $this->is_external_type( $element_type_prefix ) ) {
			$item = apply_filters( 'wpml_get_translatable_item', null, $post_id, $element_type_prefix );
		}

		if ( ! $item ) {
			$item = get_post( $post_id );
		}

		return $item;
	}

	private function init_comments_synchronization() {
		if ( wpml_get_setting_filter( null, 'sync_comments_on_duplicates' ) ) {
			add_action( 'delete_comment', array( $this, 'duplication_delete_comment' ) );
			add_action( 'edit_comment', array( $this, 'duplication_edit_comment' ) );
			add_action( 'wp_set_comment_status', array( $this, 'duplication_status_comment' ), 10, 2 );
			add_action( 'wp_insert_comment', array( $this, 'duplication_insert_comment' ), 100 );
		}
	}

	private function init_default_settings() {
		if ( ! isset( $this->settings[ $this->get_translation_setting_name( 'custom-fields' ) ] ) ) {
			$this->settings[ $this->get_translation_setting_name( 'custom-fields' ) ] = array();
		}

		if ( ! isset( $this->settings[ $this->get_readonly_translation_setting_name( 'custom-fields' ) ] ) ) {
			$this->settings[ $this->get_readonly_translation_setting_name( 'custom-fields' ) ] = array();
		}

		if ( ! isset( $this->settings[ $this->get_custom_translation_setting_name( 'custom-fields' ) ] ) ) {
			$this->settings[ $this->get_custom_translation_setting_name( 'custom-fields' ) ] = array();
		}

		if ( ! isset( $this->settings[ $this->get_custom_readonly_translation_setting_name( 'custom-fields' ) ] ) ) {
			$this->settings[ $this->get_custom_readonly_translation_setting_name( 'custom-fields' ) ] = array();
		}

		if ( ! isset( $this->settings['doc_translation_method'] ) ) {
			$this->settings['doc_translation_method'] = ICL_TM_TMETHOD_MANUAL;
		}
	}

	public function init_current_translator() {
		if ( did_action( 'init' ) ) {
			global $current_user;
			$current_translator = null;
			$user               = false;
			if ( isset( $current_user->ID ) ) {
				$user = new WP_User( $current_user->ID );
			}

			if ( $user && isset( $user->data ) && $user->data ) {
				$current_translator               = new WPML_Translator();
				$current_translator->ID           = $current_user->ID;
				$current_translator->user_login   = isset( $user->data->user_login ) ? $user->data->user_login : false;
				$current_translator->display_name = isset( $user->data->display_name ) ? $user->data->display_name : $current_translator->user_login;
				$current_translator               = $this->init_translator_language_pairs( $current_user, $current_translator );
			}

			$this->current_translator = $current_translator;
		}
	}

	public function get_translation_setting_name( $section ) {
		return $this->get_sanitized_translation_setting_section( $section ) . '_translation';
	}

	public function get_custom_translation_setting_name( $section ) {
		return $this->get_translation_setting_name( $section ) . '_custom';
	}

	public function get_custom_readonly_translation_setting_name( $section ) {
		return $this->get_custom_translation_setting_name( $section ) . '_readonly';
	}

	public function get_readonly_translation_setting_name( $section ) {
		return $this->get_sanitized_translation_setting_section( $section ) . '_readonly_config';
	}

	private function get_sanitized_translation_setting_section( $section ) {
		$section = preg_replace( '/-/', '_', $section );
		return $section;
	}

	private function assign_translation_job( $job_id, $translator_id, $service = 'local', $type = 'post' ) {
		do_action( 'wpml_tm_assign_translation_job', $job_id, $translator_id, $service, $type );

		return true;
	}

	/**
	 * @param string $table
	 *
	 * @return string[]
	 */
	private function initial_translation_states( $table ) {
		global $wpdb;

		$custom_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$table}" );

		return $custom_keys;
	}

	/**
	 * Save notification settings.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_save_notification_settings( $data ) {
		if ( wp_verify_nonce(
			$data['save_notification_settings_nonce'],
			'save_notification_settings_nonce'
		)
		) {
			foreach (
				array(
					'new-job',
					'include_xliff',
					'resigned',
					'completed',
					'completed_frequency',
					'overdue',
					'overdue_offset',
				) as $setting
			) {
				if ( ! array_key_exists( $setting, $data['notification'] ) ) {
					$data['notification'][ $setting ] = ICL_TM_NOTIFICATION_NONE;
				}
			}

			$this->settings['notification'] = $data['notification'];
			$this->save_settings();
			$message = array(
				'id'   => 'icl_tm_message_save_notification_settings',
				'type' => 'updated',
				'text' => __( 'Preferences saved.', 'sitepress' ),
			);
			ICL_AdminNotifier::add_message( $message );
			do_action( 'wpml_tm_notification_settings_saved', $this->settings['notification'] );
		}
	}

	/**
	 * Cancel translation jobs.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_cancel_jobs( $data ) {
		$message = array(
			'id'   => 'icl_tm_message_cancel_jobs',
			'type' => 'updated',
		);
		if ( isset( $data['icl_translation_id'] ) ) {
			$this->cancel_translation_request( $data['icl_translation_id'] );
			$message['text'] = __( 'Translation requests cancelled.', 'sitepress' );
		} else {
			$message['text'] = __( 'No Translation requests selected.', 'sitepress' );
		}
		ICL_AdminNotifier::add_message( $message );
	}

	/** @return int */
	public function get_init_priority() {
		return self::INIT_PRIORITY;
	}

	/**
	 * @param array $translation_status_data
	 *
	 * @return mixed
	 */
	private function get_translation_prev_state( array $translation_status_data ) {
		$prevstate = array();

		if ( ! empty( $translation_status_data ) ) {
			$keys = array(
				'status',
				'translator_id',
				'needs_update',
				'md5',
				'translation_service',
				'translation_package',
				'timestamp',
				'links_fixed',
			);

			$prevstate = array_intersect_key( $translation_status_data, array_flip( $keys ) );
		}

		return $prevstate;
	}

	private function is_unlocked_type( $type, $unlocked_options ) {
		return isset( $unlocked_options[ $type ] ) && $unlocked_options[ $type ];
	}

	private function displayMessageThatJobsCreated() {
		if ( Option::getTranslateEverything() ) {
			return;
		}
		$translationsText = esc_html__(
			'WPML → Translations',
			'wpml-translation-management'
		);

		$automaticTranslationTabText = esc_html__(
			'Automatic Translation',
			'wpml-translation-management'
		);

		$translationsLink            = '<a href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php' ) . '">' . $translationsText . '</a>';
		$automaticTranslationTabLink = '<a href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=ate-ams' ) . '">' . $automaticTranslationTabText . '</a>';

		$messageText = sprintf( __( 'To translate your content, go to %1$s. Or, go to the %2$s tab to automatically translate your content in bulk.', 'wpml-translation-management' ), $translationsLink, $automaticTranslationTabLink );

		$message = [
			'id'            => 'icl_tm_message_translation_confirmation',
			'type'          => 'updated',
			'text'          => $messageText,
			'admin_notice'  => true,
			'show_once'     => true,
			'limit_to_page' => [ WPML_TM_FOLDER . '/menu/main.php' ],
		];

		ICL_AdminNotifier::add_message( $message );
	}
}
