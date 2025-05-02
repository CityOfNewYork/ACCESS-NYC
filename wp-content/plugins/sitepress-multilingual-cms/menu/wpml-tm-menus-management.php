<?php

use WPML\API\Sanitize;
use WPML\Setup\Option;
use WPML\TM\ATE\ClonedSites\Lock as AteApiLock;
use WPML\LIB\WP\User;
use function WPML\Container\make;

class WPML_TM_Menus_Management extends WPML_TM_Menus {

	const SKIP_TM_WIZARD_META_KEY = 'wpml_skip_tm_wizard';

	private $active_languages;
	private $translatable_types;
	private $current_language;
	private $filter_post_status;
	private $filter_translation_type;
	private $post_statuses;
	private $selected_languages;
	private $source_language;
	/** @var \WPML_TM_Translation_Priorities */
	private $translation_priorities;
	private $dashboard_title_sort_link;
	private $dashboard_date_sort_link;
	private $documents;
	private $selected_posts = array();
	private $translation_filter;
	private $found_documents;
	/**
	 * @var \Mockery\MockInterface
	 */
	private $admin_sections;

	public function __construct() {
		$this->admin_sections = WPML\Container\make( 'WPML_TM_Admin_Sections' );
		$this->admin_sections->init_hooks();

		parent::__construct();
	}

	public function renderEmbeddedDashboard( $embeddedRenderCallback ) {
		if ( true !== apply_filters( 'wpml_tm_lock_ui', false ) ) {
			$this->render_main( $embeddedRenderCallback );
		}
	}

	protected function render_main( $embeddedRenderCallback = null ) {
		if ( ! AteApiLock::isLocked() ) {
			?>
		<div class="wrap">
			<?php if ( $embeddedRenderCallback === null ): ?>
				<h1><?php echo esc_html__( 'Translation Management', 'wpml-translation-management' ); ?></h1>
			<?php endif; ?>

			<?php
			do_action( 'icl_tm_messages' );
			$this->build_tab_items($embeddedRenderCallback);
			$this->render_items();
			?>
		</div>
			<?php
		}
	}

	/**
	 * It builds all the sections.
	 */
	protected function build_tab_items($embeddedRenderCallback=null) {
		$this->tab_items = array();

		$this->build_dashboard_item( $embeddedRenderCallback );

		/** @var \WPML_TM_Admin_Sections $admin_sections */
		foreach ( $this->admin_sections->get_tab_items() as $slug => $tab_item ) {

			$this->tab_items[ $slug ] = $tab_item;
		}

		$this->build_translation_jobs_item();
		$this->build_tp_com_log_item();

		$this->reorder_items();
	}

	/**
	 * It reorders all items based on their `order` key as well as the order (index) they were added.
	 */
	private function reorder_items() {
		$order_of_sections = array();
		$tab_items         = $this->tab_items;

		foreach ( $tab_items as $key => $value ) {
			$order_of_sections[ $key ] = (int) $value['order'];
		}

		if ( array_multisort( $order_of_sections, SORT_ASC, $tab_items ) ) {
			$this->tab_items = $tab_items;
		}
	}

	private function build_dashboard_item( $embeddedRenderCallback = null ) {
		$this->tab_items['dashboard'] = [
			'caption'          => __( 'Translation Dashboard', 'sitepress' ),
			'current_user_can' => [ User::CAP_ADMINISTRATOR, User::CAP_MANAGE_TRANSLATIONS ],
			'callback'         => $embeddedRenderCallback ?: [ $this, 'build_content_dashboard' ],
			'order'            => 100,
		];
	}

	public function build_content_dashboard() {
		/** @var SitePress $sitepress */
		global $sitepress;
		$this->active_languages   = $sitepress->get_active_languages();
		$this->translatable_types = apply_filters( 'wpml_tm_dashboard_translatable_types', $sitepress->get_translatable_documents() );
		$this->build_dashboard_data();

		if ( $this->found_documents > $this->documents || $this->there_are_hidden_posts() ) {
			$this->display_hidden_posts_message();
		}

		$this->build_dashboard_confirmation_messages_container();
		$this->build_content_dashboard_remote_translations_controls();
		$this->build_content_dashboard_filter();
		$this->build_content_dashboard_results();
	}

	/**
	 * Used only by unit tests at the moment
	 */
	private function build_dashboard_data() {
		$this->build_dashboard_filter_arguments();
		$this->build_dashboard_documents();
	}

	private function build_dashboard_filter_arguments() {
		global $sitepress, $iclTranslationManagement;

		$this->current_language = $sitepress->get_current_language();
		$this->source_language  = TranslationProxy_Basket::get_source_language();

        $this->translation_filter = \WPML\TM\TranslationDashboard\FiltersStorage::get();

		if ( $this->source_language || ! isset( $this->translation_filter['from_lang'] ) ) {
			if ( $this->source_language ) {
				$this->translation_filter['from_lang'] = $this->source_language;
			} else {
				$this->translation_filter['from_lang'] = $this->current_language;
				if ( $lang = Sanitize::stringProp( 'lang', $_GET ) ) {
					$this->translation_filter['from_lang'] = $lang;
				}
			}
		}

		if ( ! isset( $this->translation_filter['to_lang'] ) ) {
			$this->translation_filter['to_lang'] = '';
			if ( $lang = Sanitize::stringProp( 'to_lang', $_GET ) ) {
				$this->translation_filter['to_lang'] = $lang;
			}
		}

		if ( $this->translation_filter['to_lang'] == $this->translation_filter['from_lang'] ) {
			$this->translation_filter['to_lang'] = false;
		}

		if ( ! isset( $this->translation_filter['tstatus'] ) ) {
			$this->translation_filter['tstatus'] = isset( $_GET['tstatus'] ) ? $_GET['tstatus'] : -1; // -1 == All documents
		}

		if ( ! isset( $this->translation_filter['sort_by'] ) || ! $this->translation_filter['sort_by'] ) {
			$this->translation_filter['sort_by'] = 'date';
		}
		if ( ! isset( $this->translation_filter['sort_order'] ) || ! $this->translation_filter['sort_order'] ) {
			$this->translation_filter['sort_order'] = 'DESC';
		}
		if ( ! isset( $this->translation_filter['type'] ) ) {
			$this->translation_filter['type'] = ''; // All Types.
		}
		$sort_order_next                 = $this->translation_filter['sort_order'] == 'ASC' ? 'DESC' : 'ASC';
        $nonce                           = wp_create_nonce( 'sort' );
		$this->dashboard_title_sort_link = 'admin.php?page=' . WPML_TM_FOLDER . $this->get_page_slug() . '&sm=dashboard&icl_tm_action=sort&sort_by=title&sort_order=' . $sort_order_next . '&nonce=' . $nonce;
		$this->dashboard_date_sort_link  = 'admin.php?page=' . WPML_TM_FOLDER . $this->get_page_slug() . '&sm=dashboard&icl_tm_action=sort&sort_by=date&sort_order=' . $sort_order_next . '&nonce=' . $nonce;

		$this->post_statuses          = array(
			'publish' => __( 'Published', 'wpml-translation-management' ),
			'draft'   => __( 'Draft', 'wpml-translation-management' ),
			'pending' => __( 'Pending Review', 'wpml-translation-management' ),
			'future'  => __( 'Scheduled', 'wpml-translation-management' ),
			'private' => __( 'Private', 'wpml-translation-management' ),
		);
		$this->post_statuses          = apply_filters( 'wpml_tm_dashboard_post_statuses', $this->post_statuses );
		$this->translation_priorities = new WPML_TM_Translation_Priorities();

		// Get the document types that we can translate
		/**
		 * attachments are excluded
		 *
		 * @since 2.6.0
		 */
		add_filter( 'wpml_tm_dashboard_translatable_types', array( $this, 'exclude_attachments' ) );
		$this->post_types = $sitepress->get_translatable_documents();
		$this->post_types = apply_filters( 'wpml_tm_dashboard_translatable_types', $this->post_types );
		$this->build_external_types();

		$this->selected_languages = array();
		if ( ! empty( $iclTranslationManagement->dashboard_select ) ) {
			$this->selected_posts     = $iclTranslationManagement->dashboard_select['post'];
			$this->selected_languages = $iclTranslationManagement->dashboard_select['translate_to'];
		}
		if ( isset( $this->translation_filter['icl_selected_posts'] ) ) {
			parse_str( $this->translation_filter['icl_selected_posts'], $this->selected_posts );
		}

		$this->filter_post_status = isset( $this->translation_filter['status'] ) ? $this->translation_filter['status'] : false;

		if ( isset( $_GET['type'] ) ) {
			$this->translation_filter['type'] = $_GET['type'];
		}

		$paged                            = (int) filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
		$this->translation_filter['page'] = $paged ? $paged - 1 : 0;
		$this->filter_translation_type    = isset( $this->translation_filter['type'] ) ? $this->translation_filter['type'] : false;
	}

	private function build_dashboard_confirmation_messages_container() {
		echo '<div id="wpml-tm-dashboard-sent-content-messages-container"></div>';
	}

	private function build_dashboard_documents() {
		global $wpdb, $sitepress;
		$wpml_tm_dashboard_pagination = new WPML_TM_Dashboard_Pagination();
		$wpml_tm_dashboard_pagination->add_hooks();
		$tm_dashboard                         = new WPML_TM_Dashboard( $wpdb, $sitepress );
		$this->translation_filter['limit_no'] = $this->dashboard_pagination ? $this->dashboard_pagination->get_items_per_page() : 20;
		$dashboard_data                       = $tm_dashboard->get_documents( $this->translation_filter );
		$this->documents                      = $dashboard_data['documents'];
		$this->found_documents                = $dashboard_data['found_documents'];
	}

	/**
	 * @return bool
	 */
	private function there_are_hidden_posts() {
		return -1 === $this->found_documents;
	}

	private function display_hidden_posts_message() {
		?>
		<div class="notice notice-warning otgs-notice-icon inline">
			<p>
			<?php
				echo sprintf(
					esc_html__( 'To see more items, use the filter and narrow down the search. %s', 'wpml-translation-management' ),
					'<a href="https://wpml.org/documentation/translating-your-contents/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmltm" target="_blank">' . esc_html__( 'Help', 'wpml-translation-management' ) . '</a>'
				)
			?>
				</p>
		</div>
		<?php
	}

	private function build_content_dashboard_remote_translations_controls() {
		// shows only when translation polling is on and there are translations in progress
		$this->build_content_dashboard_fetch_translations_box();

		$active_service         = icl_do_not_promote() ? false : TranslationProxy::get_current_service();
		$service_dashboard_info = TranslationProxy::get_service_dashboard_info();
		if ( $active_service && $service_dashboard_info ) {
			?>
			<div class="icl_cyan_box">
				<h3>
				<?php
				echo $active_service->name . ' ' . __(
					'account status',
					'wpml-translation-management'
				)
				?>
																 </h3>
				<?php echo $service_dashboard_info; ?>
			</div>
			<?php
		}
	}

	private function build_content_dashboard_results() {
		?>
		<form method="post" id="icl_tm_dashboard_form">
			<?php
			// #############################################
			// Display the items for translation in a table.
			// #############################################
			$this->build_content_dashboard_documents();

			$this->heading( __( '2. Select translation options', 'wpml-translation-management' ) );
			$this->build_content_dashboard_documents_options();
			do_action( 'wpml_tm_dashboard_promo' );
			?>

		</form>
		<?php
	}

	private function build_content_dashboard_documents() {
		?>

		<input type="hidden" name="icl_tm_action" value="add_jobs"/>
		<input type="hidden" name="translate_from" value="<?php echo esc_attr( $this->translation_filter['from_lang'] ); ?>"/>
		<table class="widefat fixed striped" id="icl-tm-translation-dashboard">
			<thead>
			<?php $this->build_content_dashboard_documents_head_footer_cells(); ?>
			</thead>
			<tfoot>
			<?php $this->build_content_dashboard_documents_head_footer_cells(); ?>
			</tfoot>
			<tbody>
			<?php
			$this->build_content_dashboard_documents_body();
			?>
			</tbody>
		</table>
		<div class="tablenav clearfix">
			<div class="alignleft">
				<strong><?php echo esc_html__( 'Word count estimate:', 'wpml-translation-management' ); ?></strong>
				<?php printf( esc_html__( '%s words', 'wpml-translation-management' ), '<span id="icl-tm-estimated-words-count">0</span>' ); ?>
				<span id="icl-tm-doc-wrap" style="display: none">
					<?php printf( esc_html__( 'in %s document(s)', 'wpml-translation-management' ), '<span id="icl-tm-sel-doc-count">0</span>' ); ?>
				</span>
				<?php do_action( 'wpml_tm_dashboard_word_count_estimation' ); ?>
			</div>
			<?php
			if ( $this->dashboard_pagination ) {
				do_action( 'wpml_tm_dashboard_pagination', $this->dashboard_pagination->get_items_per_page(), $this->found_documents );
			}
			?>
		</div>
		<?php
		do_action( 'wpml_tm_after_translation_dashboard_documents' );
	}

	private function build_content_dashboard_documents_options() {
		echo '<div class="tm-dashboard-translation-options"></div>';
	}


	private function build_content_dashboard_documents_head_footer_cells() {
		global $sitepress;
		?>
		<tr>
			<td scope="col" class="manage-column column-cb check-column">
				<?php
				$check_all_checked = checked( true, isset( $_GET['post_id'] ), false );
				?>
				<input type="checkbox" <?php echo $check_all_checked; ?>/>
			</td>
			<th scope="col" class="manage-column column-title">
				<?php
				$dashboard_title_sort_caption = __( 'Title', 'wpml-translation-management' );
				$this->build_content_dashboard_documents_sorting_link( $this->dashboard_title_sort_link, $dashboard_title_sort_caption, 'p.post_title' );
				?>
			</th>
			<th scope="col" class="manage-column wpml-column-type">
				<?php echo esc_html__( 'Type', 'wpml-translation-management' ); ?>
			</th>
			<?php
			$active_languages = $sitepress->get_active_languages();
			$lang_count       = count( $active_languages );
			$lang_col_width   = ( $lang_count - 1 ) * 32 . 'px';
			if ( $lang_count > 10 ) {
				$lang_col_width = '30%';
			}
			?>

			<th scope="col" class="manage-column column-active-languages wpml-col-languages" style="width: <?php echo esc_attr( $lang_col_width ); ?>">
				<?php
				if ( $this->translation_filter['to_lang'] && array_key_exists( $this->translation_filter['to_lang'], $active_languages ) ) {
					$lang = $active_languages[ $this->translation_filter['to_lang'] ];
					?>
					<span title="<?php echo esc_attr( $lang['display_name'] ); ?>">
                        <?php echo $sitepress->get_flag_image( $lang['code'], [ 16, 12 ], $this->translation_filter['to_lang'] ) ?>
                    </span>
					<?php
				} else {
					foreach ( $active_languages as $lang ) {
						if ( $lang['code'] === $this->translation_filter['from_lang'] ) {
							continue;
						}
						?>
						<span title="<?php echo esc_attr( $lang['display_name'] ); ?>">
                            <?php echo $sitepress->get_flag_image( $lang['code'], [ 16, 12 ], $lang['code'] ) ?>
                        </span>
						<?php
					}
				}
				?>
			</th>
			<th scope="col" class="manage-column column-date">
				<?php
				$dashboard_date_sort_label = __( 'Date', 'wpml-translation-management' );
				$this->build_content_dashboard_documents_sorting_link( $this->dashboard_date_sort_link, $dashboard_date_sort_label, 'p.post_date' );
				?>
			</th>
			<th scope="col" class="manage-column column-actions">
				<?php echo esc_html__( 'Actions', 'wpml-translation-management' ); ?>
			</th>

		</tr>
		<?php
	}

	private function build_content_dashboard_documents_body() {
		global $sitepress;

		if ( ! $this->documents ) {
			?>
			<tr>
				<td scope="col" colspan="6" align="center">
					<span class="no-documents-found"><?php echo esc_html__( 'No documents found', 'wpml-translation-management' ); ?></span>
				</td>
			</tr>
			<?php
		} else {
			$records_factory               = new WPML_TM_Word_Count_Records_Factory();
			$single_process_factory        = new WPML_TM_Word_Count_Single_Process_Factory();
			$translatable_element_provider = new WPML_TM_Translatable_Element_Provider(
				$records_factory->create(),
				$single_process_factory->create(),
				class_exists( 'WPML_ST_Package_Factory' ) ? new WPML_ST_Package_Factory() : null
			);

			wp_nonce_field( 'save_translator_note_nonce', '_icl_nonce_stn_' );
			$active_languages = $this->translation_filter['to_lang']
				? array( $this->translation_filter['to_lang'] => $this->active_languages[ $this->translation_filter['to_lang'] ] )
				: $this->active_languages;
			foreach ( $this->documents as $doc ) {
				$selected = is_array( $this->selected_posts ) && in_array( $doc->ID, $this->selected_posts );
				$doc_row  = new WPML_TM_Dashboard_Document_Row(
					$doc,
					$this->post_types,
					$this->post_statuses,
					$active_languages,
					$selected,
					$sitepress,
					$translatable_element_provider
				);
				$doc_row->display();
			}
		}
	}

	private function build_content_dashboard_documents_sorting_link( $url, $label, $filter_argument ) {
		$caption = $label;
		if ( $this->translation_filter['sort_by'] === $filter_argument ) {
			$caption .= '&nbsp;';
			$caption .= $this->translation_filter['sort_order'] === 'ASC' ? '&uarr;' : '&darr;';
		}
		?>
		<a href="<?php echo esc_url( $url ); ?>">
			<?php echo $caption; ?>
		</a>
		<?php
	}


	private function build_translation_jobs_item() {
		$jobs_repository = wpml_tm_get_jobs_repository();
		$jobs_count      = $jobs_repository->get_count( new WPML_TM_Jobs_Search_Params() );

		if ( $jobs_count ) {
			$this->tab_items['jobs'] = [
				'caption'          => __( 'Translation Jobs', 'sitepress' ),
				'current_user_can' => [ User::CAP_ADMINISTRATOR, User::CAP_MANAGE_TRANSLATIONS ],
				'callback'         => [ $this, 'build_content_translation_jobs' ],
				'order'            => 100000,
				'visible'          => ! Option::shouldTranslateEverything(),
			];
		}
	}

	public function build_content_translation_jobs() {
		echo "<div id='wpml-remote-jobs-container'></div>";
	}


	private function build_tp_com_log_item() {
		if ( isset( $_GET['sm'] ) && 'com-log' === $_GET['sm'] ) {
			$this->tab_items['com-log'] = array(
				'caption'          => __( 'Communication Log', 'wpml-translation-management' ),
				'current_user_can' => 'manage_options',
				'callback'         => array( $this, 'build_tp_com_log' ),
				'order'            => 1000000,
			);
		}
	}

	public function build_tp_com_log() {
		if ( isset( $_POST['tp-com-clear-log'] ) ) {
			WPML_TranslationProxy_Com_Log::clear_log();
		}

		if ( isset( $_POST['tp-com-disable-log'] ) ) {
			WPML_TranslationProxy_Com_Log::set_logging_state( false );
		}

		if ( isset( $_POST['tp-com-enable-log'] ) ) {
			WPML_TranslationProxy_Com_Log::set_logging_state( true );
		}

		$action_url = esc_attr( 'admin.php?page=' . WPML_TM_FOLDER . $this->get_page_slug() . '&sm=' . $_GET['sm'] );
		$com_log    = WPML_TranslationProxy_Com_Log::get_log();

		?>

		<form method="post" id="tp-com-log-form" name="tp-com-log-form" action="<?php echo $action_url; ?>">

			<?php if ( WPML_TranslationProxy_Com_Log::is_logging_enabled() ) : ?>

				<?php echo esc_html__( "This is a log of the communication between your site and the translation system. It doesn't include any private information and allows WPML support to help with problems related to sending content to translation.", 'wpml-translation-management' ); ?>

				<br />
				<br />
				<?php if ( $com_log != '' ) : ?>
					<textarea wrap="off" readonly="readonly" rows="16" style="font-size:10px; width:100%"><?php echo $com_log; ?></textarea>
					<br />
					<br />
					<input class="button-secondary" type="submit" name="tp-com-clear-log" value="<?php echo esc_attr__( 'Clear log', 'wpml-translation-management' ); ?>">
				<?php else : ?>
					<strong><?php echo esc_html__( 'The communication log is empty.', 'wpml-translation-management' ); ?></strong>
					<br />
					<br />
				<?php endif; ?>

				<input class="button-secondary" type="submit" name="tp-com-disable-log" value="<?php echo esc_attr__( 'Disable logging', 'wpml-translation-management' ); ?>">

			<?php else : ?>
				<?php echo esc_html__( 'Communication logging is currently disabled. To allow WPML support to help you with issues related to sending content to translation, you need to enable the communication logging.', 'wpml-translation-management' ); ?>

				<br />
				<br />
				<input class="button-secondary" type="submit" name="tp-com-enable-log" value="<?php echo esc_attr__( 'Enable logging', 'wpml-translation-management' ); ?>">

			<?php endif; ?>

		</form>
		<?php
	}

	public function get_dashboard_documents() {
		return $this->documents;
	}

	public function build_content_dashboard_filter() {
		global $wpdb;

		$dashboard_filter = new WPML_TM_Dashboard_Display_Filter(
			$this->active_languages,
			$this->source_language,
			$this->translation_filter,
			$this->post_types,
			$this->post_statuses,
			$this->translation_priorities->get_values(),
			$wpdb
		);
		$dashboard_filter->display();
	}

	private function build_external_types() {
		$this->post_types = apply_filters( 'wpml_get_translatable_types', $this->post_types );
		foreach ( $this->post_types as $id => $type_info ) {
			if ( isset( $type_info->prefix ) ) {
				// this is an external type returned by wpml_get_translatable_types
				$new_type                        = new stdClass();
				$new_type->labels                = new stdClass();
				$new_type->labels->singular_name = isset( $type_info->labels->singular_name ) ? $type_info->labels->singular_name : $type_info->label;
				$new_type->labels->name          = isset( $type_info->labels->name ) ? $type_info->labels->name : $type_info->label;
				$new_type->prefix                = $type_info->prefix;
				$new_type->external_type         = 1;

				$this->post_types[ $id ] = $new_type;
			}
		}
	}

	/**
	 * @param array $post_types
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	public function exclude_attachments( $post_types ) {
		unset( $post_types['attachment'] );

		return $post_types;
	}

	protected function get_page_slug() {
		return WPML_Translation_Management::PAGE_SLUG_MANAGEMENT;
	}

	protected function get_default_tab() {
		return 'dashboard';
	}

	/**
	 * @return bool|\TranslationProxy_Service|\WP_Error
	 */
	private function is_translation_service_enabled() {
		$translation_service_enabled = TranslationProxy::get_current_service();
		if ( is_wp_error( $translation_service_enabled ) ) {
			$translation_service_enabled = false;
		}

		return $translation_service_enabled;
	}

	public static function getInstance() {
	 	return make( self::class );
	}
}
