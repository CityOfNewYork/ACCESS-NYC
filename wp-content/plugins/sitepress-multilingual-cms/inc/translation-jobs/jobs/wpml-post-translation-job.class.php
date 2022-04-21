<?php

use WPML\FP\Obj;
use WPML\TM\Jobs\FieldId;
use WPML\TM\Jobs\TermMeta;
use WPML\FP\Lst;

require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-translation-job.class.php';

class WPML_Post_Translation_Job extends WPML_Element_Translation_Job {

	function get_original_document() {

		return get_post( $this->get_original_element_id() );
	}

	/**
	 * @param bool|false $original
	 *
	 * @return string
	 */
	public function get_url( $original = false ) {
		$url        = null;
		$element_id = null;

		if ( $original ) {
			$element_id = $this->get_original_element_id();
			$url        = get_permalink( $element_id );
		} else {
			$element_id = $this->get_resultant_element_id();
			$url        = get_edit_post_link( $element_id );
		}

		return apply_filters( 'wpml_element_translation_job_url', $url, $original, $element_id, $this->get_original_document() );
	}

	/**
	 * It checks that the post type is translatable.
	 *
	 * @return bool
	 */
	function is_translatable_post_type() {
		$post_type = $this->get_post_type();

		if ( $post_type ) {
			/** @var SitePress $sitepress */
			global $sitepress;
			if ( $sitepress ) {
				$post_types = array_keys( $sitepress->get_translatable_documents() );

				return in_array( $post_type, $post_types, true );
			}
		}

		return false;
	}

	function update_fields_from_post() {
		global $iclTranslationManagement, $wpdb;

		$job_id           = $this->get_id();
		$post_id          = $this->get_resultant_element_id();
		$data['complete'] = 1;
		$data['job_id']   = $job_id;
		$job              = wpml_tm_load_job_factory()->get_translation_job( $job_id, 1 );
		$term_names       = $this->get_term_field_array_for_post();
		$post             = get_post( $post_id );
		if ( is_object( $job ) && is_array( $job->elements ) && is_object( $post ) ) {
			foreach ( $job->elements as $element ) {
				$field_data = '';
				switch ( $element->field_type ) {
					case 'title':
						$field_data = $this->encode_field_data( $post->post_title);
						break;
					case 'body':
						$field_data = $this->encode_field_data( $post->post_content);
						break;
					case 'excerpt':
						$field_data = $this->encode_field_data( $post->post_excerpt);
						break;
					case 'URL':
						$field_data = $this->encode_field_data( $post->post_name);
						break;
					default:
						if ( isset( $term_names[ $element->field_type ] ) ) {
							$field_data = $this->encode_field_data( $term_names[ $element->field_type ]);
						}
				}
				if ( $field_data ) {
					$wpdb->update( $wpdb->prefix . 'icl_translate',
						array(
							'field_data_translated' => $field_data,
							'field_finished'        => 1
						),
						array( 'tid' => $element->tid )
					);
				}
			}
			$iclTranslationManagement->mark_job_done( $job_id );
		}
	}

	function save_terms_to_post() {
		/** @var SitePress $sitepress */
		global $sitepress, $wpdb;

		$lang_code = $this->get_language_code();

		if ( $sitepress->get_setting( 'tm_block_retranslating_terms' ) ) {
			$this->load_terms_from_post_into_job( true );
		}
		$terms = $this->get_terms_in_job_rows();
		foreach ( $terms as $term ) {
			$new_term_action = new WPML_Update_Term_Action( $wpdb, $sitepress, [
				'term'        => base64_decode( $term->field_data_translated ),
				'description' => TermMeta::getTermDescription( $this->get_id(), $term->term_taxonomy_id ),
				'lang_code'   => $lang_code,
				'trid'        => $term->trid,
				'taxonomy'    => $term->taxonomy
			] );
			$new_term = $new_term_action->execute();

			foreach ( TermMeta::getTermMeta( $this->get_id(), $term->term_taxonomy_id ) as $meta ) {
				update_term_meta( $new_term['term_taxonomy_id'], FieldId::getTermMetaKey( $meta->field_type ), $meta->field_data_translated );
			}
		}

		$term_helper = wpml_get_term_translation_util();
		$term_helper->sync_terms( $this->get_original_element_id(), $this->get_language_code() );
	}

	function load_terms_from_post_into_job( $delete = null ) {
		global $sitepress;

		$delete = isset( $delete ) ? $delete : $sitepress->get_setting( 'tm_block_retranslating_terms' );
		$this->set_translated_term_values( $delete );
	}

	/**
	 * @return string
	 */
	public function get_title() {
		$title = $this->get_title_from_db();

		if ( $title ) {
			return $title;
		}

		$original_post = $this->get_original_document();

		return is_object( $original_post ) && isset( $original_post->post_title )
			? $original_post->post_title : $this->original_del_text;
	}

	/**
	 * @return string
	 */
	public function get_type_title() {
		$post_type = get_post_type_object( $this->get_post_type() );

		return $post_type->labels->singular_name;
	}

	/**
	 * @return string
	 */
	public function get_post_type() {
		$original_post = $this->get_original_document();

		return $original_post->post_type;
	}

	protected function load_resultant_element_id() {
		global $wpdb;
		$this->maybe_load_basic_data();

		return $wpdb->get_var( $wpdb->prepare( "SELECT element_id
												FROM {$wpdb->prefix}icl_translations
												WHERE translation_id = %d
												LIMIT 1",
			$this->basic_data->translation_id ) );
	}

	protected function get_terms_in_job_rows(){
		global $wpdb;

		$query_for_terms_in_job = $wpdb->prepare("	SELECT
													  tt.taxonomy,
													  tt.term_taxonomy_id,
													  iclt.trid,
													  j.field_data_translated
													FROM {$wpdb->term_taxonomy} tt
													JOIN {$wpdb->prefix}icl_translations iclt
														ON iclt.element_id = tt.term_taxonomy_id
															AND CONCAT('tax_', tt.taxonomy) = iclt.element_type
													JOIN {$wpdb->prefix}icl_translate j
														ON j.field_type = CONCAT('t_', tt.term_taxonomy_id)
													WHERE j.job_id = %d ", $this->get_id());

		return $wpdb->get_results( $query_for_terms_in_job );
	}

	/**
	 * Retrieves an array of all terms associated with a post. This array is indexed by indexes of the for {t_}{term_taxonomy_id}.
	 *
	 * @return array
	 */
	protected function get_term_field_array_for_post() {
		global $wpdb;

		$post_id = $this->get_resultant_element_id();
		$query = $wpdb->prepare( "SELECT o.term_taxonomy_id, t.name
								  FROM {$wpdb->term_relationships} o
								  JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = o.term_taxonomy_id
								  JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
								  WHERE o.object_id = %d",
			$post_id );
		$res   = $wpdb->get_results( $query );

		$result = array();

		foreach ( $res as $term ) {
			$result[ 't_' . $term->term_taxonomy_id ] = $term->name;
		}

		return $result;
	}

	protected function set_translated_term_values( $delete ) {
		global $wpdb;

		$translations_table = $wpdb->prefix . 'icl_translations';
		$translate_table    = $wpdb->prefix . 'icl_translate';

		$job_id                         = $this->get_id();
		$get_target_terms_for_job_query = $wpdb->prepare( "
					SELECT
					  t.name,
					  tt.description,
					  iclt_original.element_id ttid,
					  t.term_id tr_ttid
					FROM {$wpdb->terms} t
					JOIN {$wpdb->term_taxonomy} tt
						ON t.term_id = tt.term_id
					JOIN {$translations_table} iclt_translation
						ON iclt_translation.element_id = tt.term_taxonomy_id
							AND CONCAT('tax_', tt.taxonomy) = iclt_translation.element_type
					JOIN {$translations_table} iclt_original
						ON iclt_original.trid = iclt_translation.trid
					JOIN {$translate_table} jobs
						ON jobs.field_type = CONCAT('t_', iclt_original.element_id)
					WHERE jobs.job_id = %d
						AND iclt_translation.language_code = %s",
			$job_id, $this->get_language_code() );

		$term_values = $wpdb->get_results( $get_target_terms_for_job_query );
		foreach ( $term_values as $term ) {
			if ( $delete ) {
				$conditions = [
					"field_type LIKE 'tfield-%-{$term->ttid}'",  // Term fields
					"field_type LIKE 'tfield-%-{$term->ttid}\_%'", // Term fields as array
					"field_type = 't_{$term->ttid}'",
					"field_type = 'tdesc_{$term->ttid}'",
				];
				$wpdb->query(
					"DELETE FROM {$translate_table} WHERE job_id = $job_id AND "
					. "(" . Lst::join( ' OR ', $conditions ) . ")"
				);
			} else {
				$wpdb->update(
					$translate_table,
					[ 'field_data_translated' => base64_encode( $term->name ), 'field_finished' => 1 ],
					[ 'field_type' => 't_' . $term->ttid, 'job_id' => $job_id ]
				);
				$wpdb->update(
					$translate_table,
					[ 'field_data_translated' => base64_encode( $term->description ), 'field_finished' => 1 ],
					[ 'field_type' => 'tdesc_' . $term->ttid, 'job_id' => $job_id ]
				);

				$meta_values = $wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->termmeta} WHERE term_id = {$term->tr_ttid}" );
				foreach ( $meta_values as $meta ) {
					$wpdb->update(
						$translate_table,
						[ 'field_finished' => 1, 'field_data_translated' => base64_encode( $meta->meta_value ) ],
						[ 'job_id' => $job_id, 'field_type' => 'tfield-' . $meta->meta_key . '-' . $term->ttid ]
					);
				}
			}
		}
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	protected function filter_is_translator_args( array $args ) {
		return Obj::assoc( 'post_id', $this->get_original_element_id(), $args );
	}
}
