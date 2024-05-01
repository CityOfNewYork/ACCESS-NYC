<?php

use \WPML\TM\Jobs\FieldId;

class WPML_Translation_Editor_UI {
	const MAX_ALLOWED_SINGLE_LINE_LENGTH = 50;

	/** @var SitePress $sitepress */
	private $sitepress;
	/** @var WPDB $wpdb */
	private $wpdb;

	/** @var array */
	private $all_translations;
	/**
	 * @var WPML_Translation_Editor
	 */
	private $editor_object;
	private $job;
	private $original_post;
	private $rtl_original;
	private $rtl_original_attribute_object;
	private $rtl_translation;
	private $rtl_translation_attribute;
	private $is_duplicate = false;
	/**
	 * @var TranslationManagement
	 */
	private $tm_instance;

	/** @var WPML_Element_Translation_Job|WPML_External_Translation_Job */
	private $job_instance;

	private $job_factory;
	private $job_layout;
	/** @var array */
	private $fields;

	function __construct( wpdb $wpdb, SitePress $sitepress, TranslationManagement $iclTranslationManagement, WPML_Element_Translation_Job $job_instance, WPML_TM_Job_Action_Factory $job_factory, WPML_TM_Job_Layout $job_layout ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;

		$this->tm_instance  = $iclTranslationManagement;
		$this->job_instance = $job_instance;
		$this->job          = $job_instance->get_basic_data();
		$this->job_factory  = $job_factory;
		$this->job_layout   = $job_layout;
		if ( $job_instance->get_translator_id() <= 0 ) {
			$job_instance->assign_to( $sitepress->get_wp_api()->get_current_user_id() );
		}

		add_action( 'admin_print_footer_scripts', [ $this, 'force_uncompressed_tinymce' ], 1 );
	}

	/**
     * Force using uncompressed version tinymce which solves:
     * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-191
     *
     * Seams the compressed and uncompressed have some difference, because even WP has a force_uncompressed_tinymce
     * method, which is triggered whenever a custom theme on TinyMCE is used.
     *
	 * @return void
	 */
    public function force_uncompressed_tinymce() {
        if( ! function_exists( 'wp_scripts' ) || ! function_exists( 'wp_register_tinymce_scripts' ) ) {
            // WP is below 5.0.
            return;
        }

		$wp_scripts = wp_scripts();
		$wp_scripts->remove( 'wp-tinymce' );
		wp_register_tinymce_scripts( $wp_scripts, true );
    }

	function render() {
		list( $this->rtl_original, $this->rtl_translation ) = $this->init_rtl_settings();

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		?>
		<div class="wrap icl-translation-editor wpml-dialog-translate">
			<h1 id="wpml-translation-editor-header" class="wpml-translation-title"></h1>
			<?php
			do_action( 'icl_tm_messages' );
			do_action( 'wpml_tm_editor_messages' );
			$this->init_original_post();
			$this->init_editor_object();

			$this->output_model();
			$this->output_ate_notice();
			$this->output_gutenberg_notice();
			$this->output_wysiwyg_editors();
			$this->output_copy_all_dialog();
			if ( $this->is_duplicate ) {
				$this->output_edit_independently_dialog();
			}
			$this->output_editor_form();
			?>
		</div>
		<?php
	}

	/**
	 * @return array
	 */
	private function init_rtl_settings() {
		$this->rtl_original                  = $this->sitepress->is_rtl( $this->job->source_language_code );
		$this->rtl_translation               = $this->sitepress->is_rtl( $this->job->language_code );
		$this->rtl_original_attribute_object = $this->rtl_original ? ' dir="rtl"' : ' dir="ltr"';
		$this->rtl_translation_attribute     = $this->rtl_translation ? ' dir="rtl"' : ' dir="ltr"';

		return array( $this->rtl_original, $this->rtl_translation );
	}

	private function init_original_post() {
		// we do not need the original document of the job here
		// but the document with the same trid and in the $this->job->source_language_code
		$this->all_translations = $this->sitepress->get_element_translations( $this->job->trid, $this->job->original_post_type );
		$this->original_post    = false;
		foreach ( (array) $this->all_translations as $t ) {
			if ( $t->language_code === $this->job->source_language_code ) {
				$this->original_post = $this->tm_instance->get_post( $t->element_id, $this->job->element_type_prefix );
				// if this fails for some reason use the original doc from which the trid originated
				break;
			}
		}
		if ( ! $this->original_post ) {
			$this->original_post = $this->tm_instance->get_post( $this->job_instance->get_original_element_id(), $this->job->element_type_prefix );
		}

		if ( isset( $this->all_translations[ $this->job->language_code ] ) ) {
			$post_status        = new WPML_Post_Status( $this->wpdb, $this->sitepress->get_wp_api() );
			$this->is_duplicate = $post_status->is_duplicate( $this->all_translations[ $this->job->language_code ]->element_id );
		}

		return $this->original_post;
	}

	private function init_editor_object() {
		global $wpdb;

		$this->editor_object = new WPML_Translation_Editor( $this->sitepress, $wpdb, $this->job_instance );
	}

	private function output_model() {

		$model = array(
			'requires_translation_complete_for_each_field' => true,
			'hide_empty_fields'                            => true,
			'translation_is_complete'                      => ICL_TM_COMPLETE === (int) $this->job->status,
			'show_media_button'                            => false,
			'is_duplicate'                                 => $this->is_duplicate,
			'display_hide_completed_switcher'              => true,
		);

		if ( ! empty( $_GET['return_url'] ) ) {
			$model['return_url'] = filter_var( $_GET['return_url'], FILTER_SANITIZE_URL );
		} else {
			$model['return_url'] = 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php';
		}

		$languages          = new WPML_Translation_Editor_Languages( $this->sitepress, $this->job );
		$model['languages'] = $languages->get_model();

		$header          = new WPML_Translation_Editor_Header( $this->job_instance );
		$model['header'] = $header->get_model();

		$model['note'] = $this->sitepress->get_wp_api()->get_post_meta(
			$this->job_instance->get_original_element_id(),
			WPML_TM_Translator_Note::META_FIELD_KEY,
			true
		);

		$this->fields                = $this->job_factory->field_contents( (int) $this->job_instance->get_id() )->run();
		$this->fields                = $this->add_titles_and_adjust_styles( $this->fields );
		$this->fields                = $this->add_rtl_attributes( $this->fields );
		$model['fields']             = $this->fields;
		$model['layout']             = $this->job_layout->run( $model['fields'], $this->tm_instance );
		$model['rtl_original']       = $this->rtl_original;
		$model['rtl_translation']    = $this->rtl_translation;
		$model['translation_memory'] = (bool) $this->sitepress->get_setting( 'translation_memory', 1 );

		$model = $this->filter_the_model( $model );
		?>
			<script type="text/javascript">
				var WpmlTmEditorModel = <?php echo wp_json_encode( $model ); ?>;
			</script>
		<?php
	}

	private function output_ate_notice() {

		$html_fields = array_filter(
			$this->fields,
			function ( $field ) {
				return $field['field_style'] === '1' && strpos( $field['field_data'], '<' ) !== false;
			}
		);

		if ( count( $html_fields ) > 0 ) {
			$link        = 'https://wpml.org/documentation/translating-your-contents/advanced-translation-editor/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmltm#html-markers';
			$notice_text = esc_html__( 'We see you\'re translating content that contains HTML. Switch to the Advanced Translation Editor to translate content without the risk of breaking your HTML code.', 'wpml-translation-management' );
			echo '<div class="notice notice-info">
					<p>' . $notice_text . ' <a href="' . $link . '" class="wpml-external-link" target="_blank" rel="noopener">' . esc_html__( 'Read more...', 'wpml-translation-management' ) . '</a></p>
				</div>';
		}
	}

	private function output_gutenberg_notice() {
		$has_gutenberg_block = false;

		foreach ( $this->fields as $field ) {
			if ( preg_match( '#<!-- wp:#', $field['field_data'] ) ) {
				$has_gutenberg_block = true;
				break;
			}
		}

		if ( $has_gutenberg_block ) {
			echo '<div class="notice notice-info">
					<p>' . esc_html__( 'This content came from the Block editor and you need to translate it carefully so that formatting is not broken.', 'wpml-translation-management' ) . '</p>
					<p><a href="https://wpml.org/documentation/getting-started-guide/translating-content-created-using-gutenberg-editor/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmltm" class="wpml-external-link" target="_blank" rel="noopener">' . esc_html__( 'Learn how to translate content that comes from Block editor', 'wpml-translation-management' ) . '</a></p>
				</div>';
		}
	}

	private function output_wysiwyg_editors() {
		echo '<div style="display: none">';

		foreach ( $this->fields as $field ) {
			if ( 2 === (int) $field['field_style'] ) {
				$this->editor_object->output_editors( $field );
			}
		}

		echo '</div>';
	}

	private function output_copy_all_dialog() {
		?>
		<div id="wpml-translation-editor-copy-all-dialog" class="wpml-dialog" style="display:none"
			 title="<?php echo esc_attr__( 'Copy all fields from original', 'wpml-translation-management' ); ?>">
			<p class="wpml-dialog-cols-icon">
				<i class="otgs-ico-copy wpml-dialog-icon-xl"></i>
			</p>

			<div class="wpml-dialog-cols-content">
				<p>
					<strong><?php echo esc_html__( 'Some fields in translation are already filled!', 'wpml-translation-management' ); ?></strong>
					<br/>
					<?php echo esc_html__( 'You have two ways to copy content from the original language:', 'wpml-translation-management' ); ?>
				</p>
				<ul>
					<li><?php echo esc_html__( 'copy to empty fields only', 'wpml-translation-management' ); ?></li>
					<li><?php echo esc_html__( 'copy and overwrite all fields', 'wpml-translation-management' ); ?></li>
				</ul>
			</div>

			<div class="wpml-dialog-footer">
				<div class="alignleft">
					<button
						class="cancel wpml-dialog-close-button js-copy-cancel"><?php echo esc_html__( 'Cancel', 'wpml-translation-management' ); ?></button>
				</div>
				<div class="alignright">
					<button
						class="button-secondary js-copy-not-translated"><?php echo esc_html__( 'Copy to empty fields only', 'wpml-translation-management' ); ?></button>
					<button
						class="button-secondary js-copy-overwrite"><?php echo esc_html__( 'Copy & Overwrite all fields', 'wpml-translation-management' ); ?></button>
				</div>
			</div>

		</div>
		<?php
	}

	private function output_edit_independently_dialog() {
		?>
		<div id="wpml-translation-editor-edit-independently-dialog" class="wpml-dialog" style="display:none"
			 title="<?php echo esc_attr__( 'Edit independently', 'wpml-translation-management' ); ?>">
			<p class="wpml-dialog-cols-icon">
				<i class="otgs-ico-unlink wpml-dialog-icon-xl"></i>
			</p>

			<div class="wpml-dialog-cols-content">
				<p><?php esc_html_e( 'This document is a duplicate of:', 'wpml-translation-management' ); ?>
					<span class="wpml-duplicated-post-title">
							<img class="wpml-title-flag" src="<?php echo esc_attr( $this->sitepress->get_flag_url( $this->job->source_language_code ) ); ?>">
						<?php echo esc_html( $this->job_instance->get_title() ); ?>
						</span>
				</p>

				<p>
					<?php echo esc_html( sprintf( __( 'WPML will no longer synchronize this %s with the original content.', 'wpml-translation-management' ), $this->job_instance->get_type_title() ) ); ?>
				</p>
			</div>

			<div class="wpml-dialog-footer">
				<div class="alignleft">
					<button class="cancel wpml-dialog-close-button js-edit-independently-cancel"><?php echo esc_html__( 'Cancel', 'wpml-translation-management' ); ?></button>
				</div>
				<div class="alignright">
					<button class="button-secondary js-edit-independently"><?php echo esc_html__( 'Edit independently', 'wpml-translation-management' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	private function output_editor_form() {
		?>
		<form id="icl_tm_editor" method="post" action="">
			<input type="hidden" name="job_post_type" value="<?php echo esc_attr( $this->job->original_post_type ); ?>"/>
			<input type="hidden" name="job_post_id" value="<?php echo esc_attr( $this->job->original_doc_id ); ?>"/>
			<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_instance->get_id() ); ?>"/>

			<div id="wpml-translation-editor-wrapper"></div>
		</form>
		<?php
	}

	private function add_titles_and_adjust_styles( array $fields ) {
		return apply_filters( 'wpml_tm_adjust_translation_fields', $fields, $this->job, $this->original_post );
	}

	private function add_rtl_attributes( array $fields ) {
		foreach ( $fields as &$field ) {
			$field['original_direction']    = $this->rtl_original ? 'dir="rtl"' : 'dir="ltr"';
			$field['translation_direction'] = $this->rtl_translation ? 'dir="rtl"' : 'dir="ltr"';
		}

		return $fields;
	}

	private function filter_the_model( array $model ) {
		$job_details = array(
			'job_type' => $this->job->original_post_type,
			'job_id'   => $this->job->original_doc_id,
			'target'   => $model['languages']['target'],
		);
		$job         = apply_filters( 'wpml-translation-editor-fetch-job', null, $job_details );
		if ( $job ) {
			$model['requires_translation_complete_for_each_field'] = $job->requires_translation_complete_for_each_field();
			$model['hide_empty_fields']                            = $job->is_hide_empty_fields();
			$model['show_media_button']                            = $job->show_media_button();
			$model['display_hide_completed_switcher']              = $job->display_hide_completed_switcher();

			$model['fields'] = $this->add_rtl_attributes( $job->get_all_fields() );
			$this->fields    = $model['fields'];

			$model['layout'] = $job->get_layout_of_fields();
		}

		return $model;
	}
}
