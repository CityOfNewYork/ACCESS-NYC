<?php
/**
 * @package wpml-core
 */

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\LIB\WP\Attachment;
use WPML\TM\Jobs\FieldId;
use WPML\Utilities\Labels;
use function WPML\FP\spreadArgs;

class WPML_TM_Xliff_Writer {
	const TAB                    = "\t";
	const DEFAULT_GROUP          = 'Main Content';
	const DEFAULT_GROUP_ID       = 'Main_Content-0';
	const CUSTOM_FIELDS_GROUP    = 'Custom Fields';
	const CUSTOM_FIELDS_GROUP_ID = 'Custom_Fields-0';

	/**
	 * @var WPML_Translation_Job_Factory
	 */
	protected $job_factory;

	/**
	 * @var string
	 */
	private $xliff_version;

	/**
	 * @var WPML_TM_XLIFF_Shortcodes
	 */
	private $xliff_shortcodes;

	/**
	 * @var WPML_TM_XLIFF_Translator_Notes
	 */
	private $translator_notes;
	/**
	 * @var array
	 */
	private $handled_group_images;

	/**
	 * Wheter the xliff is for ATE or not.
	 * This is for an intermediate solution until wpmldev-3879 and wpmldev-3889 are solved.
	 *
	 * @var bool
	 */
	private $is_xliff_for_ate = false;

	/**
	 * @var bool
	 */
	private $use_translation_memory = false;

	/**
	 * @var null|string $valid_html_tag_regex
	 */
	private $valid_html_tag_regex;

	/**
	 * WPML_TM_xliff constructor.
	 *
	 * @param WPML_Translation_Job_Factory   $job_factory
	 * @param string                         $xliff_version
	 * @param \WPML_TM_XLIFF_Shortcodes|null $xliff_shortcodes
	 */
	public function __construct( WPML_Translation_Job_Factory $job_factory, $xliff_version = TRANSLATION_PROXY_XLIFF_VERSION, WPML_TM_XLIFF_Shortcodes $xliff_shortcodes = null ) {
		$this->job_factory   = $job_factory;
		$this->xliff_version = $xliff_version;

		if ( ! $xliff_shortcodes ) {
			$xliff_shortcodes = wpml_tm_xliff_shortcodes();
		}
		$this->xliff_shortcodes = $xliff_shortcodes;
		$this->translator_notes = new WPML_TM_XLIFF_Translator_Notes();
		$this->use_translation_memory = defined( 'WPML_USE_ST_TRANSLATION_MEMORY' ) && WPML_USE_ST_TRANSLATION_MEMORY;
	}

	/**
	 * Generate a XLIFF file for a given job.
	 *
	 * @param int $job_id
	 *
	 * @return resource XLIFF representation of the job
	 */
	public function get_job_xliff_file( $job_id ) {

		return $this->generate_xliff_file( $this->generate_job_xliff( $job_id ) );
	}

	/**
	 * Generate a XLIFF string for a given post or external type (e.g. package) job.
	 *
	 * @param int  $job_id
	 * @param bool $apply_memory
	 *
	 * @return string XLIFF representation of the job
	 */
	public function generate_job_xliff( $job_id, $apply_memory = true ) {
		/** @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;

		$job = $iclTranslationManagement->get_translation_job( (int) $job_id, true, false, 1 );

		// Important to set this before 'get_job_Translation_units_data'.
		$this->is_xliff_for_ate =
			// No translation service is used.
			// 'local' is used for all cases except translation service.
			'local' === $job->translation_service
			// ATE is enabled and activated.
			// No need to check per post if CTE is maybe active.
			// This just keeps XLIFF exports clean for CTE users.
			&& \WPML_TM_ATE_Status::is_enabled_and_activated();


		$translation_units  = $this->get_job_translation_units_data( $job, $apply_memory );
		$original           = $job_id . '-' . md5( $job_id . $job->original_doc_id );
		$original_post_type = isset( $job->original_post_type ) ? $job->original_post_type : null;



		$external_file_url = $this->get_external_url( $job );
		$this->get_translator_notes( $job );

		$xliff = $this->generate_xliff(
			$original,
			$job->source_language_code,
			$job->language_code,
			$translation_units,
			$external_file_url,
			$original_post_type
		);

		return $xliff;
	}

	/**
	 * Generate a XLIFF file for a given set of strings.
	 *
	 * @param array  $strings
	 * @param string $source_language
	 * @param string $target_language
	 *
	 * @return resource XLIFF file
	 */
	public function get_strings_xliff_file( $strings, $source_language, $target_language ) {
		$strings = $this->pre_populate_strings_with_translation_memory( $strings, $source_language, $target_language );

		return $this->generate_xliff_file(
			$this->generate_xliff(
				uniqid( 'string-', true ),
				$source_language,
				$target_language,
				$this->generate_strings_translation_units_data( $strings )
			)
		);
	}

	private function generate_xliff(
		$original_id,
		$source_language,
		$target_language,
		array $translation_units = array(),
		$external_file_url = null,
		$original_post_type = null
	) {
		/** @var \SitePress $sitepress */
		global $sitepress;


		$xliff = new WPML_TM_XLIFF( $this->get_xliff_version(), '1.0', 'utf-8' );

		$phase_group     = array();
		$phase_group     = array_merge( $phase_group, $this->xliff_shortcodes->get() );
		$phase_group     = array_merge( $phase_group, $this->translator_notes->get() );
		$post_type_phase = new WPML_TM_XLIFF_Post_Type( $original_post_type );
		$phase_group     = array_merge( $phase_group, $post_type_phase->get() );

		$source_language_domain = $sitepress->get_domain_by_language( $source_language );
		$target_language_domain = $sitepress->get_domain_by_language( $target_language );

		$jobSender = \WPML\TM\ATE\JobSender\JobSenderRepository::get();

		$string = $xliff
			->setFileAttributes(
				array(
					'original'                    => $original_id,
					'source-language'             => $source_language,
					'target-language'             => $target_language,
					'tool:source-language-domain' => $source_language_domain,
					'tool:target-language-domain' => $target_language_domain,
					'tool:sender-id'              => $jobSender->id,
					'tool:sender-username'        => $jobSender->username,
					'tool:sender-email'           => $jobSender->email,
					'tool:sender-display-name'    => $jobSender->displayName,
					'datatype'                    => 'plaintext',
				)
			)
			->setReferences(
				array(
					'external-file' => $external_file_url,
				)
			)
			->setPhaseGroup( $phase_group )
			->setTranslationUnits( $translation_units )
			->toString();

		return $string;
	}

	private function get_xliff_version() {
		switch ( $this->xliff_version ) {
			case '10':
				return '1.0';
			case '11':
				return '1.1';
			case '12':
			default:
				return '1.2';
		}
	}

	/**
	 * Generate translation units for a given set of strings.
	 *
	 * The units are the actual content to be translated
	 * Represented as a source and a target
	 *
	 * @param array $strings
	 *
	 * @return array The translation units representation
	 */
	private function generate_strings_translation_units_data( $strings ) {
		$translation_units = array();

		foreach ( $strings as $string ) {
			$id                  = 'string-' . $string->id;
			$translation_units[] = $this->get_translation_unit_data( $id, 'string', $string->value, $string->translation, $string->translated_from_memory );
		}

		return $translation_units;
	}

	/**
	 * @param stdClass[] $strings
	 * @param string     $source_lang
	 * @param string     $target_lang
	 *
	 * @return stdClass[]
	 */
	private function pre_populate_strings_with_translation_memory( $strings, $source_lang, $target_lang ) {
		$strings_to_translate    = wp_list_pluck( $strings, 'value' );
		$original_translated_map = $this->get_original_translated_map_from_translation_memory( $strings_to_translate, $source_lang, $target_lang );

		foreach ( $strings as &$string ) {
			$string->translated_from_memory = false;
			$string->translation            = $string->value;

			if ( array_key_exists( $string->value, $original_translated_map ) ) {
				$string->translation            = $original_translated_map[ $string->value ];
				$string->translated_from_memory = true;
			}
		}

		return $strings;
	}

	/**
	 * @param array  $strings_to_translate
	 * @param string $source_lang
	 * @param string $target_lang
	 *
	 * @return array
	 */
	private function get_original_translated_map_from_translation_memory( $strings_to_translate, $source_lang, $target_lang ) {
		$args = array(
			'strings'     => $strings_to_translate,
			'source_lang' => $source_lang,
			'target_lang' => $target_lang,
		);

		$strings_in_memory = apply_filters( 'wpml_st_get_translation_memory', array(), $args );

		if ( $strings_in_memory ) {
			return wp_list_pluck( $strings_in_memory, 'translation', 'original' );
		}

		return array();
	}

	/**
	 * Generate translation units.
	 *
	 * The units are the actual content to be translated
	 * Represented as a source and a target
	 *
	 * @param stdClass $job
	 * @param bool     $apply_memory
	 *
	 * @return array The translation units data
	 */
	private function get_job_translation_units_data( $job, $apply_memory ) {
		$translation_units = array();
		/** @var array $elements */
		$elements = $job->elements;

		if ( ! $elements ) {
			return $translation_units;
		}

		$elements = array_values( array_filter( $elements, function( $element ) {
			return ( 1 === (int) $element->field_translate );
		} ) );

		$elements = $this->pre_populate_elements_with_translation_memory( $elements, $job->source_language_code, $job->language_code );

		$elementsAsFields = Fns::map( Cast::toArr(), $elements );
		$elementsAsFields = apply_filters( 'wpml_tm_adjust_translation_fields', $elementsAsFields, $job, null );
		$elementsAsFields = Fns::map( function( $elementAsField ) {
			return $this->getExtraData( $elementAsField );
		}, $elementsAsFields );

		$elementsPairs = Lst::zip( $elements, $elementsAsFields );
		Fns::map( spreadArgs( function( $element, $elementAsField ) use ( &$translation_units, $apply_memory ) {
			$this->processElement( $element, $elementAsField, $translation_units, $apply_memory );
		} ), $elementsPairs );

		/**
		 * @param array    $translation_units
		 * @param stdClass $job
		 */
		return apply_filters( 'wpml_tm_adjust_translation_job', $translation_units, $job );
	}

	/**
	 * Processes the given element for translation, handling any associated extra data.
	 * Adds to the translation units array. If images are in the extra data it processes them first.
	 *
	 * @param stdClass $element
	 * @param array    $extraData
	 * @param array    $translationUnits
	 * @param boolean  $applyMemory
	 * @return void
	 */
	private function processElement( $element, $extraData, &$translationUnits, $applyMemory ) {
		if ( isset( $extraData['images'] ) && count( $extraData['images'] ) > 0 && ! $this->has_group_image_been_handled( $extraData ) ) {
			$this->handle_extra_data_images( $element, $extraData, $translationUnits );
		};

		if ( isset( $extraData['images'] ) ) {
			unset( $extraData['images'] );
		}

		$this->handle_field_data( $element, $extraData, $translationUnits, $applyMemory );
	}

	/**
	 * Checks whether a group's image(s) have already been handled.
	 *
	 * @param array $extra_data
	 * @return bool
	 */
	private function has_group_image_been_handled( $extra_data ) {
		if ( ! array_key_exists( 'group_id', $extra_data ) ) {
			return false;
		}

		$group_id = $extra_data['group_id'];

		$hash = '';
		foreach ( $extra_data['images'] as $image ) {
			$hash .= md5( $image );
		}

		if ( isset( $this->handled_group_images[ $group_id ] ) && $this->handled_group_images[ $group_id ] === $hash ) {
			return true;
		}

		$this->handled_group_images[ $group_id ] = $hash;

		return false;
	}

	/**
	 * Handles the processing of field data within an element.
	 *
	 * @param stdClass $element
	 * @param array    $extra_data
	 * @param array    $translation_units
	 * @param boolean  $apply_memory
	 * @return void
	 */
	private function handle_field_data( $element, $extra_data, &$translation_units, $apply_memory ) {
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$field_data_translated = base64_decode( $element->field_data_translated );
		$field_data            = base64_decode( $element->field_data );
		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		/**
		 * It modifies the content of a single field data which represents, for example, one paragraph in post content.
		 *
		 * @since 2.10.0
		 * @param string $field_data
		 */
		$field_data = apply_filters( 'wpml_tm_xliff_unit_field_data', $field_data );

		if ( FieldId::is_a_custom_field( $element->field_type ) ) {
			$field_data_translated = apply_filters(
				'wpml_tm_xliff_export_translated_cf',
				$field_data_translated,
				$element
			);
			$field_data            = apply_filters(
				'wpml_tm_xliff_export_original_cf',
				$field_data,
				$element
			);
		}
		// check for untranslated fields and copy the original if required.
		if ( ! null === $field_data_translated || '' === $field_data_translated ) {
			$field_data_translated = $this->remove_invalid_chars( $field_data );
		}

		$field_name = isset( $extra_data['unit'] ) ? $extra_data['unit'] : $element->field_type;

		if ( in_array( $element->field_type, [ 'title', 'body', 'excerpt', 'URL' ], true ) ) {
			$field_type_name = ucfirst( $element->field_type );
			$extra_data      = [
				'unit'     => $field_type_name,
				'type'     => 'text',
				'group'    => self::DEFAULT_GROUP,
				'group_id' => self::DEFAULT_GROUP_ID,
			];

			$field_name = $element->field_type;
		} elseif ( FieldId::is_any_term_field( $element->field_type ) ) {
			$extra_data['group']    = self::DEFAULT_GROUP . '/Taxonomies';
			$extra_data['group_id'] = self::DEFAULT_GROUP_ID . '/Taxonomies-0';

			$term_taxonomy_id = (int) FieldId::get_term_id( $element->field_type );

			$suffix = '';
			if ( FieldId::is_a_term_description( $element->field_type ) ) {
				$suffix = '-description';
			} elseif ( FieldId::is_a_term_meta( $element->field_type ) ) {
				$suffix = '-' . FieldId::getTermMetaKey( $element->field_type );
			}

			$taxonomy_details   = get_term_by( 'term_taxonomy_id', $term_taxonomy_id );
			$field_name         = $taxonomy_details->taxonomy . $suffix;
			$extra_data['unit'] = Labels::labelize( $field_name );
		}

		if ( $this->is_valid_unit_content( $field_data ) ) {

			$is_translation_memory_outdated = false;

			if ( isset( $element->field_finished, $element->has_previous_translation ) ) {
				$is_translation_memory_outdated = ( '0' === $element->field_finished && $element->has_previous_translation );
			}

			if ( $this->use_translation_memory ) {
				$is_translated_from_memory = $apply_memory && $element->translated_from_memory;
			} else {
				// When translation memory is not being used,
				// Set $is_translated_from_memory based on previous translation.
				$is_translated_from_memory = $apply_memory
				                             && $element->has_previous_translation
				                             && $element->field_finished;
			}

			$translation_units_data = $this->get_translation_unit_data(
				$element->field_type,
				$field_name,
				$field_data,
				$apply_memory ? $field_data_translated : null,
				$is_translated_from_memory,
				$element->field_wrap_tag,
				$extra_data,
				$is_translation_memory_outdated
			);

			if ( 'title' === $field_name ) {
				// Put the title at the start of the array.
				array_unshift( $translation_units, $translation_units_data );
			} else {
				$translation_units[] = $translation_units_data;
			}
		}
	}

	/**
	 * Handles extra data related to images within an element.
	 *
	 * @param stdClass $element
	 * @param array    $extra_data_images
	 * @param array    $translation_units
	 * @return void
	 */
	private function handle_extra_data_images( $element, $extra_data_images, &$translation_units ) {
		// This is only needed for ATE (to display the image in the editor).
		if ( ! $this->is_xliff_for_ate ) {
			return;
		}
		$images = $extra_data_images['images'];
		unset( $extra_data_images['images'] );

		foreach ( $images as $image ) {
			$image_extra_data = $extra_data_images;

			$image_id = $this->get_image_id_from_url( $image );
			if ( ! $image_id ) {
				continue;
			}

			$original_image_meta_data = $this->get_image_meta_by_id( $image_id, $image );

			if ( $original_image_meta_data ) {
				if ( isset( $original_image_meta_data['width'] ) ) {
					$image_extra_data['image_width'] = (string) $original_image_meta_data['width'];
				}
				if ( isset( $original_image_meta_data['height'] ) ) {
					$image_extra_data['image_height'] = (string) $original_image_meta_data['height'];
				}
				if ( isset( $original_image_meta_data['filesize'] ) ) {
					$image_extra_data['image_filesize'] = (string) $original_image_meta_data['filesize'];
				}

				$image_meta     = wp_get_attachment_metadata( $image_id );
				$thumbnail_size = isset( $image_meta['sizes']['woocommerce_thumbnail'] ) ? 'woocommerce_thumbnail' : 'thumbnail';

				if ( isset( $image_meta['sizes'][ $thumbnail_size ] ) ) {
					$thumbnail_meta = $image_meta['sizes'][ $thumbnail_size ];

					if ( isset( $thumbnail_meta['width'] ) ) {
						$image_extra_data['thumbnail_width'] = (string) $thumbnail_meta['width'];
					}
					if ( isset( $thumbnail_meta['height'] ) ) {
						$image_extra_data['thumbnail_height'] = (string) $thumbnail_meta['height'];
					}
					if ( isset( $thumbnail_meta['filesize'] ) ) {
						$image_extra_data['thumbnail_filesize'] = (string) $thumbnail_meta['filesize'];
					}
					if ( isset( $thumbnail_meta['file'] ) ) {
						$image_extra_data['thumbnail_url'] = $this->get_thumbnail_url( $image, $thumbnail_meta['file'] );
					}
				}
			}

			$field_id                            = str_replace( '-title', '', $element->field_type );
			$image_extra_data['unit']            = 'URL';
			$image_extra_data['type']            = 'text';
			$image_extra_data['image_attribute'] = 'url';

			// This id isn't used anywhere, because the field is not for translation
			// but it's important to be unique - otherwise the xliff is invalid.
			$id_image_url = $field_id . '_url-img-' . $image_id;

			// This field 'image-url' is only used by ATE and is not really something
			// that will be translated. The only purpose for this field is to provide
			// ATE the URL of the image to display it in the editor.
			// This should be replaced by xliff attributes / elements.
			// => see wpmldev-3889.
			$translation_units[] = $this->get_translation_unit_data(
				$id_image_url,
				$field_id . '_url',
				$image,
				$image,
				false,
				$element->field_wrap_tag,
				$image_extra_data
			);
		}

	}

	/**
	 * Retrieves and constructs extra data for a given field.
	 * After applying filters to adjust the title and get extra data for groups and images.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function getExtraData( $field ) {
		$fieldType = Obj::propOr( '', 'field_type', $field );
		$title     = Obj::propOr( '', 'title', $field );
		$group     = Obj::propOr( '', 'group', $field );
		$imageUrl  = Obj::propOr( '', 'image', $field );
		$purpose   = Obj::propOr( '', 'purpose', $field );

		if ( is_array( $group ) ) {
			$groupTitleString = implode( '/', array_values( $group ) );
			$groupIdString    = implode( '/', array_keys( $group ) );
		} elseif ( FieldId::is_a_custom_field( $fieldType ) ) {
			$title            = Str::pregReplace( '/^' . FieldId::CUSTOM_FIELD_PREFIX . '/', '', $title );
			$groupTitleString = self::CUSTOM_FIELDS_GROUP;
			$groupIdString    = self::CUSTOM_FIELDS_GROUP_ID;
		} else {
			$groupTitleString = self::DEFAULT_GROUP;
			$groupIdString    = self::DEFAULT_GROUP_ID;
		}

		$extradataArray = [
			'unit'     => Labels::labelize( $title ),
			'type'     => FieldId::is_a_custom_field( $fieldType ) ? 'custom_field' : 'text',
			'group'    => $groupTitleString,
			'group_id' => $groupIdString,
		];

		if ( '' !== $imageUrl ) {
			$extradataArray = array_merge(
				$extradataArray,
				[ 'images' => [ $imageUrl ] ]
			);
		}

		if ( $purpose ) {
			$extradataArray = array_merge(
				$extradataArray,
				[ 'purpose' => $purpose ]
			);
		}

		return $extradataArray;
	}

	/**
	 * @param array  $elements
	 * @param string $source_lang
	 * @param string $target_lang
	 *
	 * @return array
	 */
	private function pre_populate_elements_with_translation_memory( array $elements, $source_lang, $target_lang ) {
		$strings_to_translate = array();

		foreach ( $elements as &$element ) {

			if ( preg_match( '/^package-string/', $element->field_type ) ) {
				// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$strings_to_translate[ $element->tid ] = base64_decode( $element->field_data );
				// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			}

			$element->has_previous_translation = '' === trim( $element->field_data_translated ) ? false : true;

			$element->translated_from_memory = FieldId::is_any_term_field( $element->field_type )
				&& $element->field_data_translated
				&& $element->field_data !== $element->field_data_translated;
		}

		$original_translated_map = $this->get_original_translated_map_from_translation_memory( $strings_to_translate, $source_lang, $target_lang );

		if ( $original_translated_map ) {

			foreach ( $elements as &$element ) {

				if ( array_key_exists( $element->tid, $strings_to_translate )
					 && array_key_exists( $strings_to_translate[ $element->tid ], $original_translated_map )
				) {
					// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$element->field_data_translated = base64_encode( $original_translated_map[ $strings_to_translate[ $element->tid ] ] );
					// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$element->translated_from_memory = true;
				}
			}
		}

		return $elements;
	}

	/**
	 * Get translation unit data.
	 *
	 * @param string  $field_id                  Field ID.
	 * @param string  $field_name                Field name.
	 * @param string  $field_data                Field content.
	 * @param string  $field_data_translated     Field translated content.
	 * @param boolean $is_translated_from_memory Boolean flag - is translated from memory.
	 * @param string  $field_wrap_tag            Field wrap tag (h1...h6, etc.).
	 * @param array   $extradata
	 * @param boolean $is_translation_memory_outdated
	 *
	 * @return array
	 */
	private function get_translation_unit_data(
		$field_id,
		$field_name,
		$field_data,
		$field_data_translated,
		$is_translated_from_memory = false,
		$field_wrap_tag = '',
		$extradata = [],
		$is_translation_memory_outdated = false
	) {
		global $sitepress;

		if ( null === $field_data ) {
			$field_data = '';
		}
		if ( null === $field_data_translated ) {
			$field_data_translated = '';
		}

		$field_data = $this->remove_invalid_chars( $field_data );

		$translation_unit = array();

		$field_data            = $this->remove_line_breaks_inside_tags( $field_data );
		$field_data_translated = $this->remove_line_breaks_inside_tags( $field_data_translated );

		if ( $sitepress->get_setting( 'xliff_newlines' ) === WPML_XLIFF_TM_NEWLINES_REPLACE ) {
			$field_data            = $this->replace_new_line_with_tag( $field_data );
			$field_data_translated = $this->replace_new_line_with_tag( $field_data_translated );
		}

		$translation_unit['attributes']['resname']  = $field_name;
		$translation_unit['attributes']['restype']  = 'string';
		$translation_unit['attributes']['datatype'] = 'html';
		$translation_unit['attributes']['id']       = $field_id;
		$translation_unit['source']                 = array( 'content' => $field_data );
		$translation_unit['target']                 = array( 'content' => $field_data_translated );
		$translation_unit['note']                   = array( 'content' => $field_wrap_tag );

		if ( $extradata && $this->is_xliff_for_ate ) {
			$encoded_extradata                           = wp_json_encode( $extradata ) !== false ? wp_json_encode( $extradata ) : '';
			$translation_unit['attributes']['extradata'] = str_replace( '"', '&quot;', $encoded_extradata );
		}

		if ( $is_translated_from_memory ) {
			$translation_unit['target']['attributes'] = array(
				'state'           => 'needs-review-translation',
				'state-qualifier' => $is_translation_memory_outdated ? 'leveraged-tm' : 'tm-suggestion',
			);
		}

		return $translation_unit;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function replace_new_line_with_tag( $string ) {
		return str_replace( array( "\n", "\r" ), array( '<br class="xliff-newline" />', '' ), $string );
	}

	private function remove_line_breaks_inside_tags( $string ) {
		return preg_replace_callback( $this->get_valid_html_tag_regex(), array( $this, 'remove_line_breaks_inside_tag_callback' ), $string );
	}

	/**
	 * @return string
	 */
	private function get_valid_html_tag_regex() {
		if ( ! $this->valid_html_tag_regex ) {
			$allowed_tags               = implode( '|', array_keys( (array) wp_kses_allowed_html( 'post' ) ) );
			$this->valid_html_tag_regex = '/(<\s*(?:' . $allowed_tags . ')\b[^>]*>)/m';

		}

		return $this->valid_html_tag_regex;
	}

	/**
	 * @param array $matches
	 *
	 * @return string
	 */
	private function remove_line_breaks_inside_tag_callback( array $matches ) {
		$tag_string = preg_replace( '/([\n\r\t ]+)/', ' ', $matches[0] );
		$tag_string = preg_replace( '/(<[\s]+)/', '<', $tag_string );
		return preg_replace( '/([\s]+>)/', '>', $tag_string );
	}

	/**
	 * @param string $string
	 *
	 * Remove all characters below 0x20 except for 0x09, 0x0A and 0x0D
	 * @see https://www.w3.org/TR/xml/#charsets
	 *
	 * @return string
	 */
	private function remove_invalid_chars( $string ) {
		return preg_replace( '/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', '', $string );
	}

	/**
	 * Save a xliff string to a temporary file and return the file ressource
	 * handle
	 *
	 * @param string $xliff_content
	 *
	 * @return resource XLIFF
	 */
	private function generate_xliff_file( $xliff_content ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$file = fopen( 'php://temp', 'rb+' );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fopen

		if ( $file ) {
			// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fwrite( $file, $xliff_content );
			// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			rewind( $file );
		}

		return $file;
	}

	/**
	 * @param stdClass $job
	 *
	 * @return false|null|string
	 */
	private function get_external_url( $job ) {
		$external_file_url = null;
		if ( isset( $job->original_doc_id ) && 'post' === $job->element_type_prefix ) {
			$external_file_url = get_permalink( $job->original_doc_id );

			return $external_file_url;
		}

		return $external_file_url;
	}

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	private function is_valid_unit_content( $content ) {
		$content = preg_replace( '/[^#\w]*/u', '', $content );

		return $content || '0' === $content;
	}

	private function get_translator_notes( $job ) {
		$this->translator_notes = new WPML_TM_XLIFF_Translator_Notes(
			isset( $job->original_doc_id ) ? $job->original_doc_id : 0
		);
	}

	/**
	 * Retrieves the image_id details from the WordPress database using the image URL.
	 *
	 * @param string $image_url
	 * @return int
	 */
	private function get_image_id_from_url( $image_url ) {
		if ( ! is_string( $image_url ) || trim( $image_url ) === '' ) {
			return false;
		}

		$original_image_url = $this->is_sized( $image_url ) ? $this->get_original_image_url( $image_url ) : $image_url;

		$image_id = false !== $original_image_url ? Attachment::idFromUrl( $original_image_url ) : null;

		if ( false !== $original_image_url && ! $image_id ) {
			// Try finding the ID with '-scaled' suffix if the first attempt fails.
			$scaled_url = preg_replace( '/(\.\w+)$/', '-scaled$1', $original_image_url );
			$image_id   = Attachment::idFromUrl( $scaled_url );
		}

		if ( $image_id ) {
			return $image_id;
		}

		return false;
	}

	/**
	 * Retrieves specific image metadata by matching the size extracted from its URL.
	 *
	 * @param int    $image_id
	 * @param string $image_url
	 * @return array|null
	 */
	private function get_image_meta_by_id( $image_id, $image_url ) {
		$metadata = wp_get_attachment_metadata( $image_id );

		if ( isset( $metadata['sizes'] ) && preg_match( '/-(\d+)x(\d+)\.\w+$/', $image_url, $matches ) ) {
			$url_width  = (int) $matches[1];
			$url_height = (int) $matches[2];

			foreach ( $metadata['sizes'] as $meta_info ) {
				if ( $meta_info['width'] === $url_width && $meta_info['height'] === $url_height ) {
					return $meta_info;
				}
			}
		} elseif ( is_array( $metadata ) ) {
			return $metadata;
		}

		return null;
	}

	/**
	 * Retrieves the original image URL from a given sized URL.
	 *
	 * @param string $sized_url
	 * @return string|false
	 */
	private function get_original_image_url( $sized_url ) {
		if ( ! is_string( $sized_url ) || trim( $sized_url ) === '' ) {
			return false;
		}

		$upload_dir = wp_upload_dir();

		if ( ! isset( $upload_dir['baseurl'] ) || ! isset( $upload_dir['basedir'] ) ) {
			return false;
		}

		$relative_thumbnail_path = str_replace( $upload_dir['baseurl'] . '/', '', $sized_url );
		$original_path           = preg_replace( '/-\d+x\d+(?=\.\w+$)/', '', $relative_thumbnail_path );
		$original_full_path      = $upload_dir['basedir'] . '/' . $original_path;

		if ( $this->check_file_exists( $original_full_path ) ) {
			return $upload_dir['baseurl'] . '/' . $original_path;
		}

		return false;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function check_file_exists( $file ) {
		return file_exists( $file );
	}

	/**
	 * Checks if a given image URL corresponds to a WordPress sized image.
	 *
	 * @param string $image_url
	 * @return bool
	 */
	private function is_sized( $image_url ) {
		if ( ! is_string( $image_url ) ) {
			return false;
		}

		return preg_match( '/-\d+x\d+\.\w+$/', $image_url ) === 1;
	}

	/**
	 * Constructs the URL for a thumbnail image based on the original file URL and the thumbnail filename.
	 *
	 * @param string $original_file_url
	 * @param string $thumbnail_filename
	 * @return string|null
	 */
	private function get_thumbnail_url( $original_file_url, $thumbnail_filename ) {

		if ( ! is_string( $original_file_url ) || ! is_string( $thumbnail_filename ) || trim( $original_file_url ) === '' || trim( $thumbnail_filename ) === '' ) {
			return null;
		}

		$upload_dir = wp_upload_dir();

		if ( ! isset( $upload_dir['baseurl'] ) ) {
			return null;
		}

		$relative_path = str_replace( $upload_dir['baseurl'] . '/', '', $original_file_url );
		$directory     = dirname( $relative_path );

		return trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( $directory ) . $thumbnail_filename;
	}
}
