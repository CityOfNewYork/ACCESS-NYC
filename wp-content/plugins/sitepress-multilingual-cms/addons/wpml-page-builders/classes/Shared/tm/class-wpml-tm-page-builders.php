<?php

class WPML_TM_Page_Builders {
	const PACKAGE_TYPE_EXTERNAL = 'external';
	const TRANSLATION_COMPLETE = 10;

	const FIELD_STYLE_AREA = 'AREA';
	const FIELD_STYLE_VISUAL = 'VISUAL';
	const FIELD_STYLE_LINK = 'LINK';

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var \WPML_PB_Integration|null */
	private $wpmlPbIntegration;

	/**
	 * @param SitePress $sitepress
	 * @param WPML_PB_Integration|null $wpmlPbIntegration
	 */
	public function __construct( SitePress $sitepress, \WPML_PB_Integration $wpmlPbIntegration = null  ) {
		$this->sitepress = $sitepress;
		$this->wpmlPbIntegration = $wpmlPbIntegration;
	}

	/**
	 * Filter translation job data.
	 *
	 * @param array $translation_package Translation package.
	 * @param mixed $post                Post.
	 * @param bool  $isOriginal          If it's used as original post.
	 *
	 * @return array
	 */
	public function translation_job_data_filter( array $translation_package, $post, $isOriginal = false ) {
		if ( self::PACKAGE_TYPE_EXTERNAL !== $translation_package['type'] && isset( $post->ID ) ) {

			$post_element        = new WPML_Post_Element( $post->ID, $this->sitepress );
			$source_post_id      = $post->ID;
			$job_lang_from       = $post_element->get_language_code();

			if ( ! $post_element->is_root_source() && $isOriginal && WPML_PB_Last_Translation_Edit_Mode::is_native_editor( $post->ID ) ) {
				$this->getWpmlPbIntegration()->register_all_strings_for_translation( $post, true );
				$source_post_element = $post_element->get_translation( $job_lang_from );
			} else {
				$source_post_element = $post_element->get_source_element();
			}

			if ( $source_post_element ) {
				$source_post_id = $source_post_element->get_id();
			}

			$job_source_is_not_post_source = $post->ID !== $source_post_id;

			$string_packages = apply_filters( 'wpml_st_get_post_string_packages', false, $source_post_id );

			$translation_package['contents']['body']['translate'] = apply_filters( 'wpml_pb_should_body_be_translated', $translation_package['contents']['body']['translate'], $post );

			if ( $string_packages ) {

				foreach ( $string_packages as $package_id => $string_package ) {

					/**
					 * String package.
					 *
					 * @var WPML_Package $string_package
					 */
					$strings             = $string_package->get_package_strings();
					$string_translations = array();

					if ( $job_source_is_not_post_source ) {
						$string_translations = $string_package->get_translated_strings( array() );
					}

					foreach ( $strings as $string ) {

						if ( self::FIELD_STYLE_LINK !== $string->type ) {
							$string_value = $string->value;

							if ( isset( $string_translations[ $string->name ][ $job_lang_from ]['value'] ) ) {
								$string_value = $string_translations[ $string->name ][ $job_lang_from ]['value'];
							}

							$field_name = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug(
								$package_id,
								$string->id
							);

							$translation_package['contents'][ $field_name ] = array(
								'translate' => 1,
								// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
								'data'      => base64_encode( $string_value ),
								// phpcs:enable
								'wrap_tag'  => WPML_TM_Page_Builders_Field_Wrapper::get_wrap_tag( $string ),
								'format'    => 'base64',
							);
						}

						$translation_package['contents']['body']['translate'] = 0;

					}
				}
			}
		}

		return $translation_package;
	}

	/**
	 * @param int      $new_element_id
	 * @param array    $fields
	 * @param stdClass $job
	 */
	public function pro_translation_completed_action( $new_element_id, array $fields, stdClass $job ) {
		if ( 'post' !== $job->element_type_prefix ) {
			return;
		}

		foreach ( $fields as $field_id => $field ) {
			$field_slug = isset( $field['field_type'] ) ? $field['field_type'] : $field_id;
			$wrapper = $this->create_field_wrapper( $field_slug );
			$string_id = $wrapper->get_string_id();

			if ( $string_id ) {
				do_action(
					'wpml_add_string_translation',
					$string_id,
					$job->language_code,
					$field['data'],
					self::TRANSLATION_COMPLETE,
					$job->translator_id,
					$job->translation_service
				);
			}
		}

		do_action( 'wpml_pb_finished_adding_string_translations', $new_element_id, $job->original_doc_id, $fields );
	}

	/**
	 * Adjust translation fields.
	 *
	 * @param array    $fields Translation fields.
	 * @param stdClass $job    Translation job.
	 *
	 * @return array
	 */
	public function adjust_translation_fields_filter( array $fields, $job ) {
		foreach ( $fields as &$field ) {
			$wrapper      = $this->create_field_wrapper( $field['field_type'] );
			$type         = $wrapper->get_string_type();
			$string_title = $wrapper->get_string_title();

			if ( $string_title ) {
				$field['title'] = $string_title;
			}

			if ( isset( $field['field_wrap_tag'] ) && $field['field_wrap_tag'] ) {
				$field['title'] = isset( $field['title'] ) ? $field['title'] : '';

				$field['title'] .= ' (' . $field['field_wrap_tag'] . ')';
			}

			if ( false !== $type ) {
				switch ( $type ) {
					case self::FIELD_STYLE_AREA:
						$field['field_style'] = '1';
						break;
					case self::FIELD_STYLE_VISUAL:
						$field['field_style'] = '2';
						break;
					default:
						$field['field_style'] = '0';
						break;
				}
			}
		}

		return $fields;
	}

	/**
	 * @param array $layout
	 *
	 * @return array
	 */
	public function job_layout_filter( array $layout ) {

		$string_groups       = array();

		foreach ( $layout as $k => $field ) {
			$wrapper = $this->create_field_wrapper( $field );
			if ( $wrapper->is_valid() ) {
				$string_groups[ $wrapper->get_package_id() ][] = $field;
				unset( $layout[ $k ] );
			}
		}

		foreach ( $string_groups as $string_package_id => $fields ) {
			$string_package = apply_filters( 'wpml_st_get_string_package', false, $string_package_id );

			$section = array(
				'field_type'    => 'tm-section',
				'title'         => isset( $string_package->title ) ? $string_package->title : '',
				'fields'        => $fields,
				'empty'         => false,
				'empty_message' => '',
				'sub_title'     => '',
			);
			$layout[] = $section;
		}

		return array_values( $layout );
	}

	/**
	 * @param string $link
	 * @param int    $post_id
	 * @param string $lang
	 * @param int    $trid
	 *
	 * @return string
	 */
	public function link_to_translation_filter( $link, $post_id, $lang, $trid ) {
		/* @var WPML_TM_Translation_Status $wpml_tm_translation_status */
		global $wpml_tm_translation_status;

		$status = $wpml_tm_translation_status->filter_translation_status( null, $trid, $lang );

		if ( $link && ICL_TM_NEEDS_UPDATE === $status ) {
			$args = array(
				'update_needed' => 1,
				'trid'          => $trid,
				'language_code' => $lang,
			);

			$link = add_query_arg( $args, $link	);
		}

		return $link;
	}

	/**
	 * @param string $field_slug
	 *
	 * @return WPML_TM_Page_Builders_Field_Wrapper
	 */
	public function create_field_wrapper( $field_slug ) {
		return new WPML_TM_Page_Builders_Field_Wrapper( $field_slug );
	}

	/**
	 * @return \WPML_PB_Integration
	 *
	 */
	private function getWpmlPbIntegration() {
		if ( null === $this->wpmlPbIntegration ) {
			$this->wpmlPbIntegration = \WPML\Container\make( \WPML_PB_Integration::class );
		}
		return $this->wpmlPbIntegration;
	}
}