<?php

class WPML_TM_Translation_Status_Display {

	private $statuses = array();
	private $stats_preloaded = false;

	/**
	 * @var WPML_Post_Status
	 */
	private $status_helper;

	/**
	 * @var WPML_Translation_Job_Factory
	 */
	private $job_factory;

	/**
	 * @var WPML_TM_API
	 */
	private $tm_api;

	/**
	 * @var WPML_Post_Translation
	 */
	private $post_translations;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	private $original_links = array();
	private $tm_editor_links = array();

	/**
	 * WPML_TM_Translation_Status_Display constructor.
	 *
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 * @param WPML_Post_Status $status_helper
	 * @param WPML_Translation_Job_Factory $job_factory
	 * @param WPML_TM_API $tm_api
	 */
	public function __construct(
		wpdb $wpdb,
		SitePress $sitepress,
		WPML_Post_Status $status_helper,
		WPML_Translation_Job_Factory $job_factory,
		WPML_TM_API $tm_api
	) {
		$this->post_translations = $sitepress->post_translations();
		$this->wpdb              = $wpdb;
		$this->status_helper     = $status_helper;
		$this->job_factory       = $job_factory;
		$this->tm_api            = $tm_api;
		$this->sitepress         = $sitepress;
	}

	public function init() {
		add_action( 'wpml_cache_clear', array( $this, 'init' ), 11, 0 );
		add_filter( 'wpml_css_class_to_translation', array(
			$this,
			'filter_status_css_class'
		), 10, 4 );
		add_filter( 'wpml_link_to_translation', array(
			$this,
			'filter_status_link'
		), 10, 4 );
		add_filter( 'wpml_text_to_translation', array(
			$this,
			'filter_status_text'
		), 10, 4 );

		add_filter( 'wpml_post_status_display_html', array( $this, 'add_links_data_attributes' ), 10, 4 );

		$this->statuses = array();
	}

	private function preload_stats() {
		$this->load_stats( $this->post_translations->get_trids() );
		$this->stats_preloaded = true;
	}

	private function load_stats( $trids ) {
		if ( ! $trids ) {
			return;
		}

		$trids       = wpml_prepare_in( $trids );
		$trids_query = "translations.trid IN ( {$trids} )";
		$stats       = $this->wpdb->get_results(
			"SELECT translation_status.status, 
       				languages.code, 
       				translation_status.translator_id, 
       				translation_status.translation_service, 
       				translations.trid, 
       			    translate_job.job_id
				FROM {$this->wpdb->prefix}icl_languages languages
				LEFT JOIN {$this->wpdb->prefix}icl_translations translations
					ON languages.code = translations.language_code
				JOIN {$this->wpdb->prefix}icl_translation_status translation_status
					ON translations.translation_id = translation_status.translation_id
				JOIN {$this->wpdb->prefix}icl_translate_job translate_job
					ON translate_job.rid = translation_status.rid AND translate_job.revision IS NULL
				WHERE languages.active = 1
					AND {$trids_query}
					OR translations.trid IS NULL",
			ARRAY_A
		);
		foreach ( $stats as $element ) {
			$this->statuses[ $element['trid'] ][ $element['code'] ] = $element;
		}

	}

	public function filter_status_css_class( $css_class, $post_id, $lang, $trid ) {
		$this->maybe_load_stats( $trid );
		$element_id  = $this->post_translations->get_element_id( $lang, $trid );
		$source_lang = $this->post_translations->get_source_lang_code( $element_id );

		if ( $this->is_in_progress( $trid, $lang ) ) {
			$css_class = 'otgs-ico-in-progress';
		} elseif ( $this->is_in_basket( $trid, $lang )
		           || ( ! $this->is_lang_pair_allowed( $lang, $source_lang ) && $element_id )
		) {
			$css_class .= ' otgs-ico-edit-disabled';
		} elseif ( ! $this->is_lang_pair_allowed( $lang, $source_lang ) && ! $element_id ) {
			$css_class .= ' otgs-ico-add-disabled';
		}

		return $css_class;
	}

	public function filter_status_text( $text, $original_post_id, $lang, $trid ) {
		$source_lang = $this->post_translations->get_element_lang_code( $original_post_id );

		$this->maybe_load_stats( $trid );
		if ( $this->is_remote( $trid, $lang ) ) {
			$language = $this->sitepress->get_language_details( $lang );
			$text     = sprintf(
				__(
					"You can't edit this translation, because this translation to %s is already in progress.",
					'wpml-translation-management'
				),
				$language['display_name']
			);

		} elseif ( $this->is_in_basket( $trid, $lang ) ) {
			$text = __(
				'Cannot edit this item, because it is currently in the translation basket.',
				'wpml-translation-management'
			);
		} elseif ( $this->is_lang_pair_allowed( $lang ) && $this->is_in_progress( $trid, $lang ) ) {
			$language = $this->sitepress->get_language_details( $lang );
			$text     = sprintf( __( 'Edit the %s translation', 'wpml-translation-management' ), $language['display_name'] );
		} elseif ( ! $this->is_lang_pair_allowed( $lang, $source_lang ) ) {
			$language        = $this->sitepress->get_language_details( $lang );
			$source_language = $this->sitepress->get_language_details( $source_lang );
			$text            = sprintf(
				__( 'You don\'t have the rights to translate from %1$s to %2$s', 'wpml-translation-management' ),
				$source_language['display_name'],
				$language['display_name']
			);
		}

		return $text;
	}

	/**
	 * @param string $link
	 * @param int $post_id
	 * @param string $lang
	 * @param int $trid
	 *
	 * @return string
	 */
	public function filter_status_link( $link, $post_id, $lang, $trid ) {

		$this->original_links[ $post_id ][ $lang ][ $trid ] = $link;

		$translated_element_id = $this->post_translations->get_element_id( $lang, $trid );
		$source_lang           = $this->post_translations->get_source_lang_code( $translated_element_id );

		if ( (bool) $translated_element_id && (bool) $source_lang === false ) {
			$this->tm_editor_links[ $post_id ][ $lang ][ $trid ] = $link;
			return $link;
		}

		$this->maybe_load_stats( $trid );
		$is_remote        = $this->is_remote( $trid, $lang );
		$is_in_progress   = $this->is_in_progress( $trid, $lang );
		$use_tm_editor    = WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $post_id );
		$use_tm_editor    = apply_filters( 'wpml_use_tm_editor', $use_tm_editor );
		$source_lang_code = $this->post_translations->get_element_lang_code( $post_id );

		$is_local_job_in_progress  = $is_in_progress && ! $is_remote;
		$is_remote_job_in_progress = $is_remote && $is_in_progress;
		$translation_exists        = (bool) $translated_element_id;

		$tm_editor_link = '';

		if (
				$is_remote_job_in_progress ||
				$this->is_in_basket( $trid, $lang ) ||
				! $this->is_lang_pair_allowed( $lang, $source_lang )
		) {
			$link = '';
			$this->original_links[ $post_id ][ $lang ][ $trid ] = ''; // Also block the native editor
		} elseif ( $source_lang_code !== $lang ) {
			$job_id = null;

			if ( $is_local_job_in_progress || $translation_exists ) {
				$job_id = $this->job_factory->job_id_by_trid_and_lang( $trid, $lang );
			}

			if ( $job_id ) {
				$tm_editor_link = $this->get_link_for_existing_job( $job_id );
			} else {
				$tm_editor_link = $this->get_link_for_new_job( $trid, $lang, $source_lang_code );
			}

			if ( $is_local_job_in_progress || $use_tm_editor ) {
				$link = $tm_editor_link;
			}
		}

		$this->tm_editor_links[ $post_id ][ $lang ][ $trid ] = $tm_editor_link;

		return $link;
	}

	/**
	 * @param string $html
	 * @param int    $post_id
	 * @param string $lang
	 * @param int    $trid
	 *
	 * @return string
	 */
	public function add_links_data_attributes( $html, $post_id, $lang, $trid  ) {
		if ( ! isset(
				$this->original_links[ $post_id ][ $lang ][ $trid ],
				$this->tm_editor_links[ $post_id ][ $lang ][ $trid ]
			)
		) {
			return $html;
		}

		$data_attributes = 'data-original-link="' . $this->original_links[ $post_id ][ $lang ][ $trid ] . '"';
		$data_attributes .= ' data-tm-editor-link="' . $this->tm_editor_links[ $post_id ][ $lang ][ $trid ] . '"';
		if ( isset( $this->statuses[ $trid ][ $lang ]['job_id'] ) ) {
			$data_attributes .= ' data-tm-job-id="' . esc_attr( $this->statuses[ $trid ][ $lang ]['job_id'] ) . '"';
		}

		return str_replace( '<a ', '<a ' . $data_attributes . ' ', $html  );
	}

	private function get_link_for_new_job( $trid, $lang, $source_lang_code ) {
		$args = array(
			'trid'                 => $trid,
			'language_code'        => $lang,
			'source_language_code' => $source_lang_code
		);

		return add_query_arg( $args, $this->get_tm_editor_base_url() );
	}

	private function get_link_for_existing_job( $job_id ) {
		$args = array( 'job_id' => $job_id );

		return add_query_arg( $args, $this->get_tm_editor_base_url() );
	}

	private function get_tm_editor_base_url() {
		$args = array(
			'page'       => WPML_TM_FOLDER . '/menu/translations-queue.php',
			'return_url' => rawurlencode( esc_url_raw( stripslashes( $this->get_return_url() ) ) )
		);

		return add_query_arg( $args, 'admin.php' );
	}

	private function get_return_url() {
		$args = array( 'wpml_tm_saved', 'wpml_tm_cancel' );

		if ( wpml_is_ajax() ) {
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				return remove_query_arg( $args, $_SERVER['HTTP_REFERER'] );
			}

			return null;
		}

		return remove_query_arg( $args );
	}

	/**
	 * @param string $lang_to
	 * @param string $lang_from
	 *
	 * @return bool
	 */
	private function is_lang_pair_allowed( $lang_to, $lang_from = null ) {

		return $this->tm_api->is_translator_filter(
			false, $this->sitepress->get_wp_api()->get_current_user_id(),
			array(
				'lang_from'      => $lang_from ? $lang_from : $this->sitepress->get_current_language(),
				'lang_to'        => $lang_to,
				'admin_override' => $this->is_current_user_admin(),
			) );
	}

	private function is_current_user_admin() {

		return $this->sitepress->get_wp_api()
		                       ->current_user_can( 'manage_options' );
	}

	/**
	 * @todo make this into a proper active record user
	 *
	 * @param int $trid
	 */
	private function maybe_load_stats( $trid ) {
		if ( ! $this->stats_preloaded ) {
			$this->preload_stats();
		}

		if ( ! isset( $this->statuses[ $trid ] ) ) {
			$this->statuses[ $trid ] = array();
			$this->load_stats( array( $trid ) );
		}
	}

	private function is_remote( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ]['translation_service'] )
		       && (bool) $this->statuses[ $trid ][ $lang ]['translation_service'] !== false
		       && $this->statuses[ $trid ][ $lang ]['translation_service'] !== 'local';
	}

	private function is_in_progress( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ]['status'] ) &&
		       in_array(
			       (int) $this->statuses[ $trid ][ $lang ]['status'],
			       array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ),
			       true
		       );
	}

	private function is_in_basket( $trid, $lang ) {

		return $this->status_helper
			       ->get_status( false, $trid, $lang ) === ICL_TM_IN_BASKET;
	}
}