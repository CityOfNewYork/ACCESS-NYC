<?php

class WPML_TM_Post_Actions extends WPML_Translation_Job_Helper {

	/** @var  WPML_TM_Action_Helper $action_helper */
	private $action_helper;

	/** @var  WPML_TM_Blog_Translators $blog_translators */
	private $blog_translators;

	/** @var  WPML_TM_Records $tm_records */
	private $tm_records;

	/**
	 * WPML_TM_Post_Actions constructor.
	 *
	 * @param WPML_TM_Action_Helper    $helper
	 * @param WPML_TM_Blog_Translators $blog_translators
	 * @param WPML_TM_Records          $tm_records
	 */
	public function __construct(
		WPML_TM_Action_Helper $helper,
		WPML_TM_Blog_Translators $blog_translators,
		WPML_TM_Records $tm_records )
	{
		$this->action_helper    = $helper;
		$this->blog_translators = $blog_translators;
		$this->tm_records       = $tm_records;
	}

	public function save_post_actions( $post_id, $post, $force_set_status = false ) {
		global $wpdb, $sitepress, $current_user;

		$trid = isset( $_POST['icl_trid'] ) && is_numeric( $_POST['icl_trid'] )
			? $_POST['icl_trid'] : $sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );

		// set trid and lang code if front-end translation creating
		$trid = apply_filters( 'wpml_tm_save_post_trid_value', isset( $trid ) ? $trid : '', $post_id );
		$lang = apply_filters( 'wpml_tm_save_post_lang_value', '', $post_id );

		$trid = $this->maybe_retrive_trid_again( $trid, $post );
		$needs_second_update = array_key_exists( 'needs_second_update', $_POST ) ? (bool) $_POST['needs_second_update'] : false;

		// is this the original document?
		$is_original = empty( $trid )
			? false
			: ! (bool) $this->tm_records
				->icl_translations_by_element_id_and_type_prefix( $post_id, 'post_' . $post->post_type )
				->source_language_code();

		if( $is_original ){
			$this->save_translation_priority( $post_id );
		}

		if ( ! empty( $trid ) && ! $is_original ) {
			$lang = $lang ? $lang : $this->get_save_post_lang( $lang, $post_id );
			$res  = $wpdb->get_row( $wpdb->prepare( "
			 SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL
		 ",
				$trid ) );
			if ( $res ) {
				$original_post_id = $res->element_id;
				$from_lang        = $res->language_code;
				$original_post    = get_post( $original_post_id );
				$md5              = $this->action_helper->post_md5( $original_post );
				$translation_id   = $this->tm_records
					->icl_translations_by_trid_and_lang( $trid, $lang )
					->translation_id();
				$user_id = $current_user->ID;
				$this->maybe_add_as_translator( $user_id, $lang, $from_lang );
				if ( $translation_id ) {
					$translation_package = $this->action_helper->create_translation_package( $original_post_id );
					list( $rid, $update ) = $this->action_helper->get_tm_instance()->update_translation_status( array(
						                                                                                            'translation_id'      => $translation_id,
						                                                                                            'status'              => isset( $force_set_status ) && $force_set_status > 0 ? $force_set_status : ICL_TM_COMPLETE,
						                                                                                            'translator_id'       => $user_id,
						                                                                                            'needs_update'        => $needs_second_update,
						                                                                                            'md5'                 => $md5,
						                                                                                            'translation_service' => 'local',
						                                                                                            'translation_package' => serialize( $translation_package )
					                                                                                            ) );
					if ( ! $update ) {
						$job_id = $this->action_helper->add_translation_job( $rid, $user_id, $translation_package );
					} else {
						$job_id          = \WPML\TM\API\Job\Map::fromRid( $rid );
						if ( ! $job_id ) {
							$job_id = $this->action_helper->add_translation_job(
								$rid,
								$user_id,
								$translation_package
							);
						}
					}

					wpml_tm_load_old_jobs_editor()->set( $job_id, WPML_TM_Editors::WP );

					// saving the translation
					do_action( 'wpml_save_job_fields_from_post', $job_id );
				}
			}
		}

		if ( ! empty( $trid ) && empty( $_POST['icl_minor_edit'] ) ) {
			$is_original  = false;
			$translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type );
			foreach ( $translations as $translation ) {
				if ( $translation->original == 1 && $translation->element_id == $post_id ) {
					$is_original = true;
					break;
				}
			}

			if ( $is_original ) {
				$statusesUpdater = $this->get_translation_statuses_updater( $post_id, $translations );

				/**
				 * The filter allows to delegate the status update for translations.
				 *
				 * @param bool false (default) if we should update immediately or true if done at a different stage.
				 * @param int $post_id The original post ID.
				 * @param callable The updater function to execute.
				 *
				 * @since 2.11.0
				 *
				 */
				if ( ! apply_filters( 'wpml_tm_delegate_translation_statuses_update', false, $post_id, $statusesUpdater ) ) {
					call_user_func( $statusesUpdater );
				}
			}
		}
	}

	/**
	 * @param int        $post_id
	 * @param stdClass[] $translations
	 *
	 * @return Closure
	 */
	public function get_translation_statuses_updater( $post_id, $translations ) {
		return function () use ( $post_id, $translations ) {
			$needsUpdate = false;
			$md5 = $this->action_helper->post_md5( $post_id );
			foreach ( $translations as $translation ) {
				if ( ! $translation->original ) {
					$statusRecord = $this->tm_records->icl_translation_status_by_translation_id( $translation->translation_id );
					if ( $md5 !== $statusRecord->md5() ) {
						$needsUpdate = true;
						$status              = $statusRecord->status();
						$translation_package = $this->action_helper->create_translation_package( $post_id );
						$data                = [
							'translation_id'      => $translation->translation_id,
							'needs_update'        => 1,
							'md5'                 => $md5,
							'translation_package' => serialize( $translation_package ),
							'status'              => $status === ICL_TM_ATE_CANCELLED ? ICL_TM_NOT_TRANSLATED : $status,
						];
						$this->action_helper->get_tm_instance()->update_translation_status( $data );
					}
				}
			}
			return $needsUpdate;
		};
	}

	/**
	 * Adds the given language pair to the user.
	 *
	 * @param int    $user_id
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @used-by \WPML_TM_Post_Actions::save_post_actions to add language pairs to admin users automatically when saving
	 *                                                   a translation in a given language pair.
	 */
	private function maybe_add_as_translator( $user_id, $target_lang, $source_lang ) {

		$user = new WP_User( $user_id );
		if ( $user->has_cap( 'manage_options' ) && $target_lang && ! $this->blog_translators->is_translator( $user_id,
		                                                        array(
			                                                        'lang_from'      => $source_lang,
			                                                        'lang_to'        => $target_lang,
			                                                        'admin_override' => false
		                                                        ) )
		) {
			global $wpdb;

			$user->add_cap( WPML_Translator_Role::CAPABILITY );

			$language_pair_records = new WPML_Language_Pair_Records( $wpdb, new WPML_Language_Records( $wpdb ) );
			$language_pair_records->store( $user_id, array( $source_lang => array( $target_lang ) ) );
		}
	}

	private function get_save_post_lang( $lang, $post_id ) {
		if ( ( ! isset( $lang ) || ! $lang ) && ! empty( $_POST['icl_post_language'] ) ) {
			$lang = $_POST['icl_post_language'];
		} else {
			global $wpml_post_translations;

			$lang = $wpml_post_translations->get_element_lang_code( $post_id );
		}

		return $lang;
	}

	private function maybe_retrive_trid_again( $trid, $post ) {
		global $wpdb, $sitepress;
		$element_type_from_trid = $wpdb->get_var( $wpdb->prepare( "SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid=%d", $trid ) );
		if ( $element_type_from_trid && $post->post_type !== $element_type_from_trid ) {
			$trid = $sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
		}

		return $trid;
	}

	/**
	 * @param int $post_id
	 */
	public function save_translation_priority( $post_id ) {
		$translation_priority = (int) filter_var(
			( isset( $_POST['icl_translation_priority'] ) ? $_POST['icl_translation_priority'] : '' ),
			FILTER_SANITIZE_NUMBER_INT );

		if ( ! $translation_priority ) {
			$assigned_priority = $this->get_term_obj( $post_id );

			if ( $assigned_priority ) {
				$translation_priority = $assigned_priority->term_id;
			} else {
				$term = \WPML_TM_Translation_Priorities::get_default_term();
				if ( $term ) {
					$translation_priority = $term->term_id;
				};
			}
		}

		if ( $translation_priority ) {
			wp_set_post_terms( $post_id, array( $translation_priority ), \WPML_TM_Translation_Priorities::TAXONOMY );
		}
	}

	/**
	 * @param int $element_id
	 *
	 * @return WP_Term|null
	 */
	private function get_term_obj( $element_id ) {
		$terms = wp_get_object_terms( $element_id, \WPML_TM_Translation_Priorities::TAXONOMY );
		if ( is_wp_error( $terms ) ) {
			return null;
		}

		return empty( $terms ) ? null : $terms[0];
	}
}
