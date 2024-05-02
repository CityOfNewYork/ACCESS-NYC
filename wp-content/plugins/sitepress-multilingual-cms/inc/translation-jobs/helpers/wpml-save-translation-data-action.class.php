<?php

use WPML\Legacy\Translation\Save\SyncParentPost\SyncParentPost;

class WPML_Save_Translation_Data_Action extends WPML_Translation_Job_Helper_With_API {

	/** @var WPML_TM_Records $tm_records */
	private $tm_records;

	/** @var  array $data */
	private $data;

	private $redirect_target = false;
	private $translate_link_targets_in_posts;
	private $translate_link_targets_in_strings;

	/** @var SyncParentPost $syncParentPost */
	private $sync_parent_post;

	public function __construct( $data, $tm_records ) {
		global $wpdb, $ICL_Pro_Translation, $sitepress, $wpml_post_translations;
		parent::__construct();
		$this->data                              = $data;
		$this->tm_records                        = $tm_records;
		$translate_link_targets_global_state     = new WPML_Translate_Link_Target_Global_State( $sitepress );
		$this->translate_link_targets_in_posts   = new WPML_Translate_Link_Targets_In_Posts( $translate_link_targets_global_state, $wpdb, $ICL_Pro_Translation );
		$this->translate_link_targets_in_strings = new WPML_Translate_Link_Targets_In_Strings( $translate_link_targets_global_state, $wpdb, new WPML_WP_API(), $ICL_Pro_Translation );
		$this->sync_parent_post                  = new SyncParentPost( $wpdb, $sitepress, $wpml_post_translations );
	}

	function save_translation() {
		global $wpdb, $sitepress, $iclTranslationManagement, $wpml_post_translations;

		$new_post_id   = false;
		$is_incomplete = false;
		$data          = $this->data;
		/** @var stdClass $job */
		$job                 = ! empty( $data['job_id'] ) ? $this->get_translation_job( $data['job_id'], true ) : null;
		$needs_second_update = $job && $job->needs_update ? 1 : 0;
		$original_post       = null;
		$element_type_prefix = null;
		if ( is_object( $job ) ) {
			$element_type_prefix = $iclTranslationManagement->get_element_type_prefix_from_job( $job );
			$original_post       = $iclTranslationManagement->get_post( $job->original_doc_id, $element_type_prefix );
		}

		$is_external      = apply_filters( 'wpml_is_external', false, $element_type_prefix );
		$data_to_validate = array(
			'original_post' => $original_post,
			'type_prefix'   => $element_type_prefix,
			'data'          => $data,
			'is_external'   => $is_external,
		);

		$validation_results = $this->get_validation_results( $job, $data_to_validate );

		if ( ! $validation_results['is_valid'] ) {
			$this->handle_failed_validation( $validation_results, $data_to_validate );
			$res = false;
		} else {
			foreach ( $data['fields'] as $fieldname => $field ) {
				if ( substr( $fieldname, 0, 6 ) === 'field-' ) {
					$field = apply_filters( 'wpml_tm_save_translation_cf', $field, $fieldname, $data );
				}
				$this->save_translation_field( $field['tid'], $field );
				if ( ! isset( $field['finished'] ) || ! $field['finished'] ) {
					$is_incomplete = true;
				}
			}

			$icl_translate_job  = $this->tm_records->icl_translate_job_by_job_id( $data['job_id'] );
			$rid                = $icl_translate_job->rid();
			$translation_status = $this->tm_records->icl_translation_status_by_rid( $rid );
			$translation_id     = $translation_status->translation_id();
			if ( ( $is_incomplete === true || empty( $data['complete'] ) ) && empty( $data['resign'] ) ) {
				$iclTranslationManagement->update_translation_status(
					array(
						'translation_id' => $translation_id,
						'status'         => ICL_TM_IN_PROGRESS,
					)
				);
				$icl_translate_job->update( array( 'translated' => 0 ) );

				self::notify_job_in_progress( $element_type_prefix, $job );
			}

			$element_id = $translation_status->element_id();
			delete_post_meta( $element_id, '_icl_lang_duplicate_of' );

			if ( ! empty( $data['complete'] ) && ! $is_incomplete ) {
				$icl_translate_job->update(
					array(
						'translated'     => 1,
						'completed_date' => date( 'Y-m-d H:i:s' ),
					)
				);
				$job = $this->get_translation_job( $data['job_id'], true );

				if ( $is_external ) {
					self::save_external( $element_type_prefix, $job, [ $this, 'decode_field_data' ] );
				} else {
					if ( $element_id ) {
						$postarr['ID'] = $_POST['post_ID'] = $element_id;
					} else {
						$postarr['post_status'] = ! $sitepress->get_setting( 'translated_document_status' ) ? 'draft' : $original_post->post_status;
					}

					foreach ( $job->elements as $field ) {
						switch ( $field->field_type ) {
							case 'title':
								$postarr['post_title'] = $this->decode_field_data( $field->field_data_translated, $field->field_format );
								break;
							case 'body':
								$postarr['post_content'] = $this->decode_field_data(
									$field->field_data_translated,
									$field->field_format
								);
								break;
							case 'excerpt':
								$postarr['post_excerpt'] = $this->decode_field_data( $field->field_data_translated, $field->field_format );
								break;
							case 'URL':
								$postarr['post_name'] = $this->decode_field_data( $field->field_data_translated, $field->field_format );
								break;
							default:
								break;
						}
					}

					$postarr['post_author'] = $original_post->post_author;
					$postarr['post_type']   = $original_post->post_type;

					if ( $sitepress->get_setting( 'sync_comment_status' ) ) {
						$postarr['comment_status'] = $original_post->comment_status;
					}
					if ( $sitepress->get_setting( 'sync_ping_status' ) ) {
						$postarr['ping_status'] = $original_post->ping_status;
					}
					if ( $sitepress->get_setting( 'sync_page_ordering' ) ) {
						$postarr['menu_order'] = $original_post->menu_order;
					}
					if ( $sitepress->get_setting( 'sync_private_flag' ) && $original_post->post_status == 'private' ) {
						$postarr['post_status'] = 'private';
					}
					if ( $sitepress->get_setting( 'sync_password' ) && $original_post->post_password ) {
						$postarr['post_password'] = $original_post->post_password;
					}
					if ( $sitepress->get_setting( 'sync_post_date' ) ) {
						$postarr['post_date'] = $original_post->post_date;
					}

					$postarr = $this->sync_parent_post->linkParentTranslatedPostOrFlagOriginal( $original_post->post_parent, $job->language_code, $postarr );

					$_POST['trid']                   = $translation_status->trid();
					$_POST['lang']                   = $job->language_code;
					$_POST['skip_sitepress_actions'] = true;
					$_POST['needs_second_update']    = $needs_second_update;

					/* @deprecated Use `wpml_pre_save_pro_translation` instead */
					$postarr = apply_filters( 'icl_pre_save_pro_translation', $postarr );

					$postarr = apply_filters( 'wpml_pre_save_pro_translation', $postarr, $job );

					// it's an update and user do not want to translate urls so do not change the url
					if ( $element_id ) {
						if ( $sitepress->get_setting( 'translated_document_page_url' ) !== 'translate' ) {
							$postarr['post_name'] = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT post_name
																				 FROM {$wpdb->posts}
																			     WHERE ID=%d
																			     LIMIT 1",
									$element_id
								)
							);
						}

						$existing_post            = get_post( $element_id );
						$postarr['post_date']     = $existing_post->post_date;
						$postarr['post_date_gmt'] = $existing_post->post_date_gmt;
					}

					$new_post_id = wpml_get_create_post_helper()->insert_post( $postarr, $job->language_code );
					$this->sync_parent_post->linkUnlinkedChildPosts( $original_post->ID, $job->language_code, $new_post_id );

					$link = get_edit_post_link( $new_post_id );
					if ( '' === $link ) {
						// the current user can't edit so just include permalink.
						$link = get_permalink( $new_post_id );
					}

					if ( ! $element_id ) {
						$wpdb->delete(
							$wpdb->prefix . 'icl_translations',
							array(
								'element_id'   => $new_post_id,
								'element_type' => 'post_' . $postarr['post_type'],
							)
						);
						$wpdb->update( $wpdb->prefix . 'icl_translations', array( 'element_id' => $new_post_id ), array( 'translation_id' => $translation_id ) );
						$user_message = __( 'Translation added: ', 'wpml-translation-management' ) . '<a href="' . $link . '">' . $postarr['post_title'] . '</a>.';
					} else {
						$user_message = __( 'Translation updated: ', 'wpml-translation-management' ) . '<a href="' . $link . '">' . $postarr['post_title'] . '</a>.';
					}

					icl_cache_clear( $postarr['post_type'] . 's_per_language' ); // clear post counter per language in cache
					do_action( 'wpml_pro_translation_after_post_save', $new_post_id );

					// set taxonomies for users with limited caps
					if ( ! current_user_can( 'manage-categories' ) && ! empty( $postarr['tax_input'] ) ) {
						foreach ( $postarr['tax_input'] as $taxonomy => $terms ) {
							wp_set_post_terms( $new_post_id, $terms, $taxonomy, false ); // true to append to existing tags | false to replace existing tags
						}
					}

					$data['fields'] = apply_filters( 'wpml_tm_job_fields', $data['fields'], $job );

					do_action( 'icl_pro_translation_saved', $new_post_id, $data['fields'], $job );
					do_action( 'wpml_translation_job_saved', $new_post_id, $data['fields'], $job );

					// update body translation with the links fixed
					$new_post_content = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $new_post_id ) );
					foreach ( $job->elements as $job_element ) {
						if ( $job_element->field_type === 'body' ) {
							$fields_data_translated = apply_filters( 'wpml_tm_job_data_post_content', $new_post_content );
							$fields_data_translated = $this->encode_field_data( $fields_data_translated );
							$wpdb->update(
								$wpdb->prefix . 'icl_translate',
								array( 'field_data_translated' => $fields_data_translated ),
								array(
									'job_id'     => $data['job_id'],
									'field_type' => 'body',
								)
							);

							break;
						}
					}

					$sitepress->copy_custom_fields( $original_post->ID, $new_post_id );

					// set specific custom fields
					$copied_custom_fields = array( '_top_nav_excluded', '_cms_nav_minihome' );
					foreach ( $copied_custom_fields as $ccf ) {
						$val = get_post_meta( $original_post->ID, $ccf, true );
						update_post_meta( $new_post_id, $ccf, $val );
					}

					// sync _wp_page_template
					if ( $sitepress->get_setting( 'sync_page_template' ) ) {
						$_wp_page_template = get_post_meta( $original_post->ID, '_wp_page_template', true );
						if ( ! empty( $_wp_page_template ) ) {
							update_post_meta( $new_post_id, '_wp_page_template', $_wp_page_template );
						}
					}

					$this->package_helper->save_job_custom_fields(
						$job,
						$new_post_id,
						\WPML\TM\Settings\Repository::getCustomFields()
					);

					// set stickiness
					// is the original post a sticky post?
					$sticky_posts       = get_option( 'sticky_posts' );
					$is_original_sticky = $original_post->post_type == 'post' && in_array( $original_post->ID, $sticky_posts );

					if ( $is_original_sticky && $sitepress->get_setting( 'sync_sticky_flag' ) ) {
						stick_post( $new_post_id );
					} else {
						if ( $original_post->post_type == 'post' && ! is_null( $element_id ) ) {
							unstick_post( $new_post_id ); // just in case - if this is an update and the original post stickiness has changed since the post was sent for translation
						}
					}

					$this->add_message(
						array(
							'type' => 'updated',
							'text' => $user_message,
						)
					);
				}

				if ( $this->get_tm_setting( array( 'notification', 'completed' ) ) != ICL_TM_NOTIFICATION_NONE
					 && $data['job_id']
				) {
					do_action( 'wpml_tm_complete_job_notification', $data['job_id'], ! is_null( $element_id ) );
				}

				$iclTranslationManagement->set_page_url( $new_post_id );

				if ( isset( $job ) && isset( $job->language_code ) && isset( $job->source_language_code ) ) {
					$this->save_terms_for_job( $data['job_id'] );
				}

				// sync post format
				// Must be after save terms otherwise it gets lost.
				if ( $sitepress->get_setting( 'sync_post_format' ) ) {
					$_wp_post_format = get_post_format( $original_post->ID );
					$_wp_post_format && set_post_format( $new_post_id, $_wp_post_format );
				}

				do_action( 'icl_pro_translation_completed', $new_post_id, $data['fields'], $job );
				do_action( 'wpml_pro_translation_completed', $new_post_id, $data['fields'], $job );

				$translation_status->update( [
					'status'       => apply_filters( 'wpml_tm_applied_job_status', ICL_TM_COMPLETE, $job, $new_post_id ),
					'needs_update' => $needs_second_update,
				] );

				$this->translate_link_targets_in_posts->new_content();
				$this->translate_link_targets_in_strings->new_content();

				if ( ! defined( 'REST_REQUEST' ) && ! defined( 'XMLRPC_REQUEST' ) && ! defined( 'DOING_AJAX' ) && ! isset( $_POST['xliff_upload'] ) ) {
					$action_type           = is_null( $element_id ) ? 'added' : 'updated';
					$element_id            = is_null( $element_id ) ? $new_post_id : $element_id;
					$this->redirect_target = admin_url( sprintf( 'admin.php?page=%s&%s=%d&element_type=%s', WPML_TM_FOLDER . '/menu/translations-queue.php', $action_type, $element_id, $element_type_prefix ) );
				}
			} else {
				$this->add_message(
					array(
						'type' => 'updated',
						'text' => __( 'Translation (incomplete) saved.', 'wpml-translation-management' ),
					)
				);
			}

			$res = true;
		}

		return $res;
	}

	/**
	 * Returns false if after saving the translation no redirection is to happen or the target of the redirection
	 * in case saving the data is followed by a redirect.
	 *
	 * @return false|string
	 */
	function get_redirect_target() {

		return $this->redirect_target;
	}

	private function save_translation_field( $tid, $field ) {
		global $wpdb;

		$update = [];
		if ( isset( $field['data'] ) ) {
			$update['field_data_translated'] = $this->encode_field_data( $field['data'] );
		}
		$update['field_finished'] = isset( $field['finished'] ) && $field['finished'] ? 1 : 0;

		$wpdb->update( $wpdb->prefix . 'icl_translate', $update, array( 'tid' => $tid ) );
	}

	private function handle_failed_validation( $validation_results, $data_to_validate ) {
		if ( isset( $validation_results['messages'] ) ) {
			$messages = (array) $validation_results['messages'];
			if ( $messages ) {
				foreach ( $messages as $message ) {
					$this->add_message(
						array(
							'type' => 'error',
							'text' => $message,
						)
					);
				}
			} else {
				$this->add_message(
					array(
						'type' => 'error',
						'text' => __( 'Submitted data is not valid.', 'wpml-translation-management' ),
					)
				);
			}
		}
		do_action( 'wpml_translation_validation_failed', $validation_results, $data_to_validate );
	}

	private function get_validation_results( $job, $data_to_validate ) {
		$is_valid                   = true;
		$original_post              = $data_to_validate['original_post'];
		$element_type_prefix        = $data_to_validate['type_prefix'];
		$validation_default_results = array(
			'is_valid' => $is_valid,
			'messages' => array(),
		);
		if ( ! $job || ! $original_post || ! $element_type_prefix ) {
			$is_valid = false;
			if ( ! $job ) {
				$validation_default_results['messages'][] = __( 'Job ID is missing', 'wpml-translation-management' );
			}
			if ( ! $original_post ) {
				$validation_default_results['messages'][] = __( 'The original post cannot be retrieved', 'wpml-translation-management' );
			}
			if ( ! $element_type_prefix ) {
				$validation_default_results['messages'][] = __( 'The type of the post cannot be retrieved', 'wpml-translation-management' );
			}
		} elseif ( ! $this->tm_records->icl_translate_job_by_job_id( $job->job_id )->is_open() ) {
			$is_valid                                 = false;
			$validation_default_results['messages'][] = __( 'This job cannot be edited anymore because a newer job for this element exists.', 'wpml-translation-management' );
		}
		$validation_default_results['is_valid'] = $is_valid;
		$validation_results                     = apply_filters( 'wpml_translation_validation_data', $validation_default_results, $data_to_validate );
		$validation_results                     = array_merge( $validation_default_results, $validation_results );

		if ( ! $is_valid && $validation_results['is_valid'] ) {
			$validation_results['is_valid'] = $is_valid;
		}

		return $validation_results;
	}

	private function save_terms_for_job( $job_id ) {
		require_once WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-jobs-collection.class.php';

		$job = new WPML_Post_Translation_Job( $job_id );
		$job->save_terms_to_post();
	}

	private function add_message( $message ) {
		global $iclTranslationManagement;

		$iclTranslationManagement->add_message( $message );
	}

	/**
	 * @param string   $element_type_prefix
	 * @param object   $job
	 * @param callable $decoder
	 */
	private static function save_external( $element_type_prefix, $job, $decoder ) {
		/**
		 * Wether we should save the external package or not.
		 *
		 * Since string packages are translated automatically, they might need to be reviewed
		 * When we want to review the string package translation, we should not save it right away.
		 *
		 * @since 4.6.8
		 *
		 * @param bool   $shouldSave        Whether we should save the external package or not.
		 * @param string $elementTypePrefix The external element type prefix. Could be 'package' or 'st-batch'.
		 * @param object $job               The translation job to save.
		 */
		if ( apply_filters( 'wpml_should_save_external', true, $element_type_prefix, $job ) ) {
			/**
			 * Save the external job.
			 *
			 * String packages and string batches hooks into this action to save the strings translations.
			 *
			 * @since 4.4.0
			 *
			 * @param string   $elementTypePrefix The external element type prefix. Could be 'package' or 'st-batch'.
			 * @param object   $job               The translation job to save.
			 * @param callable $decoder           Function to decode translation values.
			 */
			do_action( 'wpml_save_external', $element_type_prefix, $job, $decoder );
		}
	}

	/**
	 * @param string $element_type_prefix
	 * @param object $job
	 */
	private static function notify_job_in_progress( $element_type_prefix, $job ) {
		/**
		 * The action triggered when a job is marked as in progress
		 *
		 * @param string $element_type_prefix
		 * @param object $job
		 * @since 2.10.0
		 */
		do_action( 'wpml_tm_job_in_progress', $element_type_prefix, $job );
	}
}
