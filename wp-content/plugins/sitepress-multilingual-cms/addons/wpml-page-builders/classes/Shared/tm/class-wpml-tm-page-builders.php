<?php

use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\PB\TranslationJob\Groups;
use WPML\PB\TranslationJob\Labels;

class WPML_TM_Page_Builders {
	const PACKAGE_TYPE_EXTERNAL = 'external';
	const TRANSLATION_COMPLETE  = 10;

	const FIELD_STYLE_AREA   = 'AREA';
	const FIELD_STYLE_VISUAL = 'VISUAL';
	const FIELD_STYLE_LINK   = 'LINK';

	const TOP_LEVEL_GROUP_ID          = 'Main_Content-0';
	const TOP_LEVEL_GROUP_DESCRIPTION = 'Main Content';

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var \WPML_PB_Integration|null */
	private $wpmlPbIntegration;

	/** @var array[] */
	private $attachments = [];
	/**
	 * @param SitePress                $sitepress
	 * @param WPML_PB_Integration|null $wpmlPbIntegration
	 */
	public function __construct( SitePress $sitepress, \WPML_PB_Integration $wpmlPbIntegration = null ) {
		$this->sitepress         = $sitepress;
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

			$post_element   = new WPML_Post_Element( $post->ID, $this->sitepress );
			$source_post_id = $post->ID;
			$job_lang_from  = $post_element->get_language_code();

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
					$string_translations = [];

					if ( $job_source_is_not_post_source ) {
						$string_translations = $string_package->get_translated_strings( [] );
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

							$translation_package['contents'][ $field_name ] = [
								'translate' => 1,
								// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
								'data'      => base64_encode( $string_value ),
								// phpcs:enable
								'wrap_tag'  => WPML_TM_Page_Builders_Field_Wrapper::get_wrap_tag( $string ),
								'format'    => 'base64',
							];
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
			$wrapper    = $this->create_field_wrapper( $field_slug );
			$string_id  = $wrapper->get_string_id();

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
			$widget_block_label_data = $this->get_block_widget_title_and_group( $field['title_fallback'] ?? '' );

			if ( ! empty( $widget_block_label_data['title'] ) ) {
				$field['title'] = $widget_block_label_data['title'];
				$field['group'] = $widget_block_label_data['group'];
				continue;
			}

			$wrapper = $this->create_field_wrapper( $field['field_type'] );
			$type    = $wrapper->get_string_type();
			$title   = $wrapper->get_string_title();

			if ( $title ) {
				$field['title'] = $title;
			}

			$string_package_id = $wrapper->get_package_id();

			if ( $string_package_id && Groups::isGroupLabel( $field['title'] ) ) {
				list( $groups, $title ) = Groups::parseGroupLabel( $field['title'] );

				$field['title'] = Labels::convertToHuman( $title );
				$field['group'] = [ self::TOP_LEVEL_GROUP_ID => self::TOP_LEVEL_GROUP_DESCRIPTION ];
				foreach ( $groups as $group ) {
					$field['group'][ $group ] = Labels::convertToHuman( $group );
				}

				$field = $this->set_image_url( $field, end( $groups ) );
			}

			$field = $this->group_media_texts( $field );

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
	 * Retrieves the title and group for a widget/block.
	 *
	 * @param string $title
	 * @return array|false Associative array with 'title' and 'group' keys, or false if not found.
	 */
	private function get_block_widget_title_and_group( $title ) {
		if ( Groups::isGroupLabel( $title ) && $this->containsBlockElement( $title ) ) {
			list( $groups, $raw_title ) = Groups::parseGroupLabel( $title );

			$clean_title = Labels::convertToHuman( $raw_title );
			$group_data  = [
				self::TOP_LEVEL_GROUP_ID => self::TOP_LEVEL_GROUP_DESCRIPTION,
			];
			foreach ( $groups as $group ) {
				$group_data[ $group ] = Labels::convertToHuman( $group );
			}

			return [
				'title' => $clean_title,
				'group' => $group_data,
			];
		}

		return false;
	}

	/**
	 * Checks if a title contains a block element pattern.
	 *
	 * @param string $title
	 * @return bool
	 */
	private function containsBlockElement( $title ) {
		return (bool) preg_match( '/block-\d+/', $title );
	}

	/**
	 * @param array  $field
	 * @param string $innerMostGroup
	 *
	 * @return array
	 */
	private function set_image_url( $field, $innerMostGroup ) {
		/**
		 * @param array $patterns
		 */
		$patterns = array_unique( apply_filters( 'wpml_pb_image_module_patterns', [] ) );

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $innerMostGroup, $matches ) ) {
				$url = wp_get_attachment_url( $matches[1] );
				if ( $url ) {
					$field['image'] = $url;

					$this->attachments[ $matches[1] ] = $field;
				}
				break;
			}
		}

		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	public function group_media_texts( $field ) {
		$fieldType = Obj::propOr( '', 'field_type', $field );
		$matches   = Str::match( '/^media_(\d+)_\w+$/', $fieldType );
		if ( isset( $matches[1] ) ) {
			$attachmentId = $matches[1];
			if ( isset( $this->attachments[ $attachmentId ]['image'] ) ) {
				$field['image'] = $this->attachments[ $attachmentId ]['image'];
				$field['group'] = $this->attachments[ $attachmentId ]['group'];
			} else {
				$url = wp_get_attachment_url( $attachmentId );
				if ( $url ) {
					$field['image'] = $url;
					$field['group'] = [
						self::TOP_LEVEL_GROUP_ID => self::TOP_LEVEL_GROUP_DESCRIPTION,
						'media_' . $attachmentId => 'Media',
					];
				}
			}
		}

		return $field;
	}

	/**
	 * @param array $elements
	 *
	 * @return array
	 */
	public function adjust_translation_job_filter( $elements ) {
		return Groups::flattenHierarchy( $elements );
	}

	/**
	 * @param array $layout
	 *
	 * @return array
	 */
	public function job_layout_filter( array $layout ) {

		$string_groups = [];

		foreach ( $layout as $k => $field ) {
			$wrapper = $this->create_field_wrapper( $field );
			if ( $wrapper->is_valid() ) {
				$string_groups[ $wrapper->get_package_id() ][] = $field;
				unset( $layout[ $k ] );
			}
		}

		foreach ( $string_groups as $string_package_id => $fields ) {
			$string_package = apply_filters( 'wpml_st_get_string_package', false, $string_package_id );

			$section  = [
				'field_type'    => 'tm-section',
				'title'         => isset( $string_package->title ) ? $string_package->title : '',
				'fields'        => $fields,
				'empty'         => false,
				'empty_message' => '',
				'sub_title'     => '',
			];
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
		/** @var WPML_TM_Translation_Status $wpml_tm_translation_status */
		global $wpml_tm_translation_status;

		$status = $wpml_tm_translation_status->filter_translation_status( null, $trid, $lang );

		if ( $link && ICL_TM_NEEDS_UPDATE === $status ) {
			$args = [
				'update_needed' => 1,
				'trid'          => $trid,
				'language_code' => $lang,
			];

			$link = add_query_arg( $args, $link );
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
	 */
	private function getWpmlPbIntegration() {
		if ( null === $this->wpmlPbIntegration ) {
			$this->wpmlPbIntegration = \WPML\Container\make( \WPML_PB_Integration::class );
		}
		return $this->wpmlPbIntegration;
	}
}
