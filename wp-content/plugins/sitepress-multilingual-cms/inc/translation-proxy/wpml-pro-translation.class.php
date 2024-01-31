<?php
/**
 * @package wpml-core
 * @package wpml-core-pro-translation
 */

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use function WPML\Container\make;
use function WPML\FP\pipe;

/**
 * Class WPML_Pro_Translation
 */
class WPML_Pro_Translation extends WPML_TM_Job_Factory_User {

	public $errors = array();
	/** @var TranslationManagement $tmg */
	private $tmg;

	/** @var  WPML_TM_CMS_ID $cms_id_helper */
	private $cms_id_helper;

	/** @var WPML_TM_Xliff_Reader_Factory $xliff_reader_factory */
	private $xliff_reader_factory;


	private $sitepress;

	private $update_pm;

	/**
	 * WPML_Pro_Translation constructor.
	 *
	 * @param WPML_Translation_Job_Factory $job_factory
	 */
	function __construct( &$job_factory ) {
		parent::__construct( $job_factory );
		global $iclTranslationManagement, $wpdb, $sitepress, $wpml_post_translations, $wpml_term_translations;

		$this->tmg                  =& $iclTranslationManagement;
		$this->xliff_reader_factory = new WPML_TM_Xliff_Reader_Factory( $this->job_factory );
		$wpml_tm_records            = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$this->cms_id_helper        = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
		$this->sitepress            = $sitepress;

		add_filter( 'xmlrpc_methods', array( $this, 'custom_xmlrpc_methods' ) );
		add_action(
			'post_submitbox_start',
			array(
				$this,
				'post_submitbox_start',
			)
		);
		add_action(
			'icl_ajx_custom_call',
			array(
				$this,
				'ajax_calls',
			),
			10,
			2
		);

		add_action( 'wpml_minor_edit_for_gutenberg', array( $this, 'gutenberg_minor_edit' ), 10, 0 );

		$this->update_pm = new WPML_Update_PickUp_Method( $this->sitepress );
	}

	/**
	 * @return WPML_TM_CMS_ID
	 */
	public function &get_cms_id_helper() {
		return $this->cms_id_helper;
	}

	/**
	 * @param string $call
	 * @param array  $data
	 */
	function ajax_calls( $call, $data ) {
		switch ( $call ) {
			case 'set_pickup_mode':
				$response = $this->update_pm->update_pickup_method( $data, $this->get_current_project() );
				if ( 'no-ts' === $response ) {
					wp_send_json_error( array( 'message' => __( 'Please activate translation service first.', 'wpml-translation-management' ) ) );
				}
				if ( 'cant-update' === $response ) {
					wp_send_json_error( array( 'message' => __( 'Could not update the translation pickup mode.', 'wpml-translation-management' ) ) );
				}

				wp_send_json_success( array( 'message' => __( 'Ok', 'wpml-translation-management' ) ) );
				break;
		}
	}

	public function get_current_project() {
		return TranslationProxy::get_current_project();
	}

	/**
	 * @param WP_Post|WPML_Package $post
	 * @param array                $target_languages
	 * @param int                  $translator_id
	 * @param int                  $job_id
	 *
	 * @return bool|int
	 */
	function send_post( $post, $target_languages, $translator_id, $job_id ) {
		/** @var TranslationManagement $iclTranslationManagement */
		global $sitepress, $iclTranslationManagement;

		$this->maybe_init_translation_management( $iclTranslationManagement );

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}
		if ( ! $post ) {
			return false;
		}

		$post_id             = $post->ID;
		$post_type           = $post->post_type;
		$element_type_prefix = $iclTranslationManagement->get_element_type_prefix_from_job_id( $job_id );
		$element_type        = $element_type_prefix . '_' . $post_type;

		$note = WPML_TM_Translator_Note::get( $post_id );
		if ( ! $note ) {
			$note = null;
		}
		$err             = false;
		$tp_job_id       = false;
		$source_language = $sitepress->get_language_for_element( $post_id, $element_type );
		$target_language = is_array( $target_languages ) ? end( $target_languages ) : $target_languages;
		if ( empty( $target_language ) || $target_language === $source_language ) {
			return false;
		}
		$translation = $this->tmg->get_element_translation( $post_id, $target_language, $element_type );
		if ( ! $translation ) { // translated the first time
			$err = true;
		}
		if ( ! $err && ( $translation->needs_update || $translation->status == ICL_TM_NOT_TRANSLATED || $translation->status == ICL_TM_WAITING_FOR_TRANSLATOR ) ) {
			$project = TranslationProxy::get_current_project();

			if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
				$job_object = new WPML_External_Translation_Job( $job_id );
			} else {
				$job_object = new WPML_Post_Translation_Job( $job_id );
				$job_object->load_terms_from_post_into_job();
			}

			list( $err, $project, $tp_job_id ) = $job_object->send_to_tp( $project, $translator_id, $this->cms_id_helper, $this->tmg, $note );
			if ( $err ) {
				$this->enqueue_project_errors( $project );
			}
		}

		return $err ? false : $tp_job_id; // last $ret
	}

	function server_languages_map( $language_name, $server2plugin = false ) {
		if ( is_array( $language_name ) ) {
			return array_map( array( $this, 'server_languages_map' ), $language_name );
		}
		$map = array(
			'Norwegian Bokmål'     => 'Norwegian',
			'Portuguese, Brazil'   => 'Portuguese',
			'Portuguese, Portugal' => 'Portugal Portuguese',
		);

		$map = $server2plugin ? array_flip( $map ) : $map;

		return isset( $map[ $language_name ] ) ? $map[ $language_name ] : $language_name;
	}

	/**
	 * @param $methods
	 *
	 * @return array
	 */
	public function custom_xmlrpc_methods( $methods ) {
		$icl_methods['translationproxy.test_xmlrpc']        = '__return_true';
		$icl_methods['translationproxy.updated_job_status'] = array(
			$this,
			'xmlrpc_updated_job_status',
		);

		$methods = array_merge( $methods, $icl_methods );
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST && preg_match( '#<methodName>([^<]+)</methodName>#i', $this->sitepress->get_wp_api()->get_raw_post_data(), $matches ) ) {
			$method = $matches[1];
			if ( array_key_exists( $method, $icl_methods ) ) {
				set_error_handler( array( $this, 'translation_error_handler' ), E_ERROR | E_USER_ERROR );
			}
		}

		return $methods;
	}

	/**
	 * @param array $args
	 *
	 * @return int|IXR_Error
	 */
	public function xmlrpc_updated_job_status( $args ) {
		global $wpdb;

		$tp_id     = isset( $args[0] ) ? $args[0] : 0;
		$cms_id    = isset( $args[1] ) ? $args[1] : 0;
		$status    = isset( $args[2] ) ? $args[2] : '';
		$signature = isset( $args[3] ) ? $args[3] : '';

		if ( ! $this->authenticate_request( $tp_id, $cms_id, $status, $signature ) ) {
			return new IXR_Error( 401, 'Wrong signature' );
		}

		try {

			/** @var WPML_TM_Jobs_Repository $jobs_repository */
			$jobs_repository = wpml_tm_get_jobs_repository();

			$job_match = $jobs_repository->get(
				new WPML_TM_Jobs_Search_Params(
					array(
						'scope' => 'remote',
						'tp_id' => $tp_id,
					)
				)
			);

			if ( $job_match ) {
				$jobs_array = $job_match->toArray();
				$job        = $jobs_array[0];
				$job->set_status( WPML_TP_Job_States::map_tp_state_to_local( $status ) );

				$tp_sync_updated_job = new WPML_TP_Sync_Update_Job( $wpdb, $this->sitepress );
				$job_updated         = $tp_sync_updated_job->update_state( $job );

				if ( $job_updated && WPML_TP_Job_States::CANCELLED !== $status ) {
					$apply_tp_translation = new WPML_TP_Apply_Single_Job(
						wpml_tm_get_tp_translations_repository(),
						new WPML_TP_Apply_Translation_Strategies( $wpdb )
					);
					$apply_tp_translation->apply( $job );

				}

				return 1;

			}
		} catch ( Exception $e ) {
			return new IXR_Error( $e->getCode(), $e->getMessage() );
		}

		return 0;
	}

	/**
	 * @return bool
	 */
	private function authenticate_request( $tp_id, $cms_id, $status, $signature ) {
		$project = TranslationProxy::get_current_project();

		return sha1( $project->id . $project->access_key . $tp_id . $cms_id . $status ) === $signature;
	}

	/**
	 * @return WPML_WP_API
	 */
	function get_wpml_wp_api() {
		return $this->sitepress->get_wp_api();
	}

	/**
	 *
	 * Cancel translation for given cms_id
	 *
	 * @param $rid
	 * @param $cms_id
	 *
	 * @return bool
	 */
	function cancel_translation( $rid, $cms_id ) {
		/**
		 * @var WPML_String_Translation|null $WPML_String_Translation
		 * @var TranslationManagement   $iclTranslationManagement
		 */
		global $WPML_String_Translation, $iclTranslationManagement;

		$res = false;
		if ( empty( $cms_id ) ) { // it's a string
			if ( $WPML_String_Translation ) {
				$res = $WPML_String_Translation->cancel_remote_translation( $rid );
			}
		} else {
			$translation_id = $this->cms_id_helper->get_translation_id( $cms_id );

			if ( $translation_id ) {
				$iclTranslationManagement->cancel_translation_request( $translation_id );
				$res = true;
			}
		}

		return $res;
	}

	/**
	 *
	 * Downloads translation from TP and updates its document
	 *
	 * @param $translation_proxy_job_id
	 * @param $cms_id
	 *
	 * @return bool|string
	 */
	function download_and_process_translation( $translation_proxy_job_id, $cms_id ) {
		global $wpdb;

		if ( empty( $cms_id ) ) { // it's a string
			// TODO: [WPML 3.3] this should be handled as any other element type in 3.3
			$target = $wpdb->get_var( $wpdb->prepare( "SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=%d", $translation_proxy_job_id ) );

			return $this->process_translated_string( $translation_proxy_job_id, $target );
		} else {
			$translation_id = $this->cms_id_helper->get_translation_id( $cms_id, TranslationProxy::get_current_service() );

			return ! empty( $translation_id ) && $this->add_translated_document( $translation_id, $translation_proxy_job_id );
		}
	}

	/**
	 * @param int $translation_id
	 * @param int $translation_proxy_job_id
	 *
	 * @return bool
	 */
	function add_translated_document( $translation_id, $translation_proxy_job_id ) {
		global $wpdb, $sitepress;
		$project = TranslationProxy::get_current_project();

		$translation_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $translation_id ) );
		$translation      = $project->fetch_translation( $translation_proxy_job_id );
		if ( ! $translation ) {
			$this->errors = array_merge( $this->errors, $project->errors );
		} else {
			$translation = apply_filters( 'icl_data_from_pro_translation', $translation );
		}
		$ret = true;

		if ( ! empty( $translation ) && strpos( $translation, 'xliff' ) !== false ) {
			try {
				/** @var $job_xliff_translation WP_Error|array */
				$job_xliff_translation = $this->xliff_reader_factory
					->general_xliff_import()->import( $translation, $translation_id );
				if ( is_wp_error( $job_xliff_translation ) ) {
					$this->add_error( $job_xliff_translation->get_error_message() );

					return false;
				}
				kses_remove_filters();
				wpml_tm_save_data( $job_xliff_translation );
				kses_init();

				$translations = $sitepress->get_element_translations( $translation_info->trid, $translation_info->element_type, false, true, true );
				if ( isset( $translations[ $translation_info->language_code ] ) ) {
					$translation = $translations[ $translation_info->language_code ];
					if ( isset( $translation->element_id ) && $translation->element_id ) {
						$translation_post_type_prepared = $wpdb->prepare( "SELECT post_type FROM $wpdb->posts WHERE ID=%d", array( $translation->element_id ) );
						$translation_post_type          = $wpdb->get_var( $translation_post_type_prepared );
					} else {
						$translation_post_type = implode( '_', array_slice( explode( '_', $translation_info->element_type ), 1 ) );
					}
					if ( $translation_post_type == 'page' ) {
						$url = get_option( 'home' ) . '?page_id=' . $translation->element_id;
					} else {
						$url = get_option( 'home' ) . '?p=' . $translation->element_id;
					}
					$project->update_job( $translation_proxy_job_id, $url );
				} else {
					$project->update_job( $translation_proxy_job_id );
				}
			} catch ( Exception $e ) {
				$ret = false;
			}
		}

		return $ret;
	}

	private static function content_get_link_paths( $body ) {

		$regexp_links = array(
			"/<a[^>]*href\s*=\s*([\"\']??)([^\"^>]+)[\"\']??([^>]*)>/i",
		);

		$links = array();

		foreach ( $regexp_links as $regexp ) {
			if ( preg_match_all( $regexp, is_null( $body ) ? '' : $body, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$links[] = $match;
				}
			}
		}

		return $links;
	}

	public function fix_links_to_translated_content( $element_id, $target_lang_code, $element_type = 'post' ) {
		global $wpdb, $sitepress;

		$sitepress->switch_lang( $target_lang_code );

		$wpml_element_type = $element_type;
		$body              = '';
		$string_type       = null;
		if ( strpos( $element_type, 'post' ) === 0 ) {
			$post_prepared     = $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID=%d", array( $element_id ) );
			$post              = $wpdb->get_row( $post_prepared );
			$body              = $post->post_content;
			$wpml_element_type = 'post_' . $post->post_type;
		} elseif ( $element_type == 'string' ) {
			$string_prepared    = $wpdb->prepare( "SELECT string_id, value FROM {$wpdb->prefix}icl_string_translations WHERE id=%d", array( $element_id ) );
			$data               = $wpdb->get_row( $string_prepared );
			$body               = $data->value;
			$original_string_id = $data->string_id;
			$string_type        = $wpdb->get_var( $wpdb->prepare( "SELECT type FROM {$wpdb->prefix}icl_strings WHERE id=%d", $original_string_id ) );
			if ( 'LINK' === $string_type ) {
				$body = '<a href="' . $body . '">removeit</a>';
			}
		}

		$translate_link_targets = make( 'WPML_Translate_Link_Targets' );
		$absolute_links         = make( 'AbsoluteLinks' );

		$getTranslatedLink = function ( $link ) use ( $translate_link_targets, $absolute_links, $element_type, $target_lang_code ) {
			if ( $absolute_links->is_home( $link[2] ) ) {
				$translatedLink = $absolute_links->convert_url( $link[2], $target_lang_code );
				$translatedLink = Str::replace( $link[2], $translatedLink, $link[0] );
			} else {
				add_filter( 'wpml_force_translated_permalink', '__return_true' );
				$translatedLink = $translate_link_targets->convert_text( $link[0] );
				remove_filter( 'wpml_force_translated_permalink', '__return_true' );
				if ( self::should_links_be_converted_back_to_sticky( $element_type ) ) {
					$translatedLink = $absolute_links->convert_text( $translatedLink );
				}
			}

			return $translatedLink !== $link[0]
				? [
					'from' => $link[0],
					'to'   => $translatedLink,
				]
				: null;
		};

		$getTranslatedLinks = pipe(
			Fns::map( $getTranslatedLink ),
			Fns::filter( Fns::identity() )
		);

		$links = self::content_get_link_paths( $body );

		$translatedLinks = $getTranslatedLinks( $links );

		$replaceLink = function ( $body, $link ) {
			return str_replace( $link['from'], $link['to'], $body );
		};

		$new_body = Fns::reduce( $replaceLink, $body, $translatedLinks );

		if ( $new_body != $body ) {
			if ( strpos( $element_type, 'post' ) === 0 ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $new_body ), array( 'ID' => $element_id ) );
			} elseif ( $element_type == 'string' ) {
				if ( 'LINK' === $string_type ) {
					$new_body = str_replace( array( '<a href="', '">removeit</a>' ), array( '', '' ), $new_body );
					$wpdb->update(
						$wpdb->prefix . 'icl_string_translations',
						array(
							'value'  => $new_body,
							'status' => ICL_TM_COMPLETE,
						),
						array( 'id' => $element_id )
					);
					do_action( 'icl_st_add_string_translation', $element_id );
				} else {
					$wpdb->update( $wpdb->prefix . 'icl_string_translations', array( 'value' => $new_body ), array( 'id' => $element_id ) );
				}
			}
		}

		$links_fixed_status_factory = new WPML_Links_Fixed_Status_Factory( $wpdb, new WPML_WP_API() );
		$links_fixed_status         = $links_fixed_status_factory->create( $element_id, $wpml_element_type );
		$links_fixed_status->set( Lst::length( $links ) === Lst::length( $translatedLinks ) );

		$sitepress->switch_lang();

		return sizeof( $translatedLinks );

	}

	function translation_error_handler( $error_number, $error_string, $error_file, $error_line ) {
		switch ( $error_number ) {
			case E_ERROR:
			case E_USER_ERROR:
				throw new Exception( $error_string . ' [code:e' . $error_number . '] in ' . $error_file . ':' . $error_line );
			case E_WARNING:
			case E_USER_WARNING:
				return true;
			default:
				return true;
		}

	}

	private static function should_links_be_converted_back_to_sticky( $element_type ) {
		return 'string' !== $element_type && ! empty( $GLOBALS['WPML_Sticky_Links'] );
	}

	function post_submitbox_start() {
		$show_box_style = $this->get_show_minor_edit_style();
		if ( false !== $show_box_style ) {
			?>
			<p id="icl_minor_change_box" style="float:left;padding:0;margin:3px;<?php echo $show_box_style; ?>">
				<label><input type="checkbox" name="icl_minor_edit" value="1" style="min-width:15px;"/>&nbsp;
					<?php esc_html_e( 'Minor edit - don\'t update translation', 'wpml-translation-management' ); ?>
				</label>
				<br clear="all"/>
			</p>
			<?php
		}
	}

	public function gutenberg_minor_edit() {
		$show_box_style = $this->get_show_minor_edit_style();
		if ( false !== $show_box_style ) {
			?>
			<div id="icl_minor_change_box" style="<?php echo $show_box_style; ?>" class="icl_box_paragraph">
				<p>
					<strong><?php esc_html_e( 'Minor edit', 'wpml-translation-management' ); ?></strong>
				</p>
				<label><input type="checkbox" name="icl_minor_edit" value="1" style="min-width:15px;"/>&nbsp;
					<?php esc_html_e( "Don't update translation", 'wpml-translation-management' ); ?>
				</label>
			</div>
			<?php
		}
	}

	private function get_show_minor_edit_style() {
		global $post, $iclTranslationManagement;
		if ( empty( $post ) || ! $post->ID ) {
			return false;
		}

		$translations   = $iclTranslationManagement->get_element_translations( $post->ID, 'post_' . $post->post_type );
		$show_box_style = 'display:none';
		foreach ( $translations as $t ) {
			if ( $t->element_id == $post->ID ) {
				return false;
			}
			if ( $t->status == ICL_TM_COMPLETE && ! $t->needs_update ) {
				$show_box_style = '';
				break;
			}
		}

		return $show_box_style;
	}

	private function process_translated_string( $translation_proxy_job_id, $language ) {
		$project     = TranslationProxy::get_current_project();
		$translation = $project->fetch_translation( $translation_proxy_job_id );
		$translation = apply_filters( 'icl_data_from_pro_translation', $translation );
		$ret         = false;
		$translation = $this->xliff_reader_factory->string_xliff_reader()->get_data( $translation );
		if ( $translation ) {
			$ret = icl_translation_add_string_translation( $translation_proxy_job_id, $translation, $language );
			if ( $ret ) {
				$project->update_job( $translation_proxy_job_id );
			}
		}

		return $ret;
	}

	private function add_error( $project_error ) {
		$this->errors[] = $project_error;
	}

	/**
	 * @param $project TranslationProxy_Project
	 */
	function enqueue_project_errors( $project ) {
		if ( isset( $project ) && isset( $project->errors ) && $project->errors ) {
			foreach ( $project->errors as $project_error ) {
				$this->add_error( $project_error );
			}
		}
	}

	/**
	 * @param TranslationManagement $iclTranslationManagement
	 */
	private function maybe_init_translation_management( $iclTranslationManagement ) {
		if ( empty( $this->tmg->settings ) ) {
			$iclTranslationManagement->init();
		}
	}
}
