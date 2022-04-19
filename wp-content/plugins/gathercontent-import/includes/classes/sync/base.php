<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Exception as Base_Exception;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
use GatherContent\Importer\Dom;
use WP_Error;

/**
 * Sync-specific exception class w/ data property.
 *
 * @since 3.0.0
 */
class Exception extends Base_Exception {}

/**
 * Base class for pushing/pulling content to GC.
 *
 * @since 3.0.0
 */
abstract class Base extends Plugin_Base {

	/**
	 * Sync direction. 'push', or 'pull'.
	 *
	 * @since  3.0.0
	 *
	 * @var string
	 */
	protected $direction = '';

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @since  3.0.0
	 *
	 * @var GatherContent\Importer\API
	 */
	protected $api = null;

	/**
	 * Async_Base instance
	 *
	 * @since  3.0.0
	 *
	 * @var Async_Base
	 */
	protected $async = null;

	/**
	 * GatherContent item object.
	 *
	 * @since  3.0.0
	 *
	 * @var null|object
	 */
	protected $item = null;

	/**
	 * GatherContent item element object.
	 *
	 * @since  3.0.0
	 *
	 * @var null|object
	 */
	protected $element = null;

	/**
	 * Mapping post object
	 *
	 * @since  3.0.0
	 *
	 * @var null|Mapping_Post
	 */
	protected $mapping = null;

	/**
	 * The WP field types which allow appending (concatenation) of content.
	 *
	 * @since  3.0.0
	 *
	 * @var array
	 */
	protected $append_types = array( 'post_content', 'post_title', 'post_excerpt' );

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param API        $api   API object.
	 * @param Async_Base $async Async_Base object.
	 */
	public function __construct( API $api, Async_Base $async ) {
		$this->api    = $api;
		$this->async  = $async;
		$this->logger = new Log();
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		$this->logger->init_hooks();
	}

	/**
	 * Handles pushing/pulling item.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $id id of thing to sync.
	 *
	 * @throws Exception On failure.
	 *
	 * @return mixed Result of push.
	 */
	abstract protected function do_item( $id );

	/**
	 * Handles syncing items for a mapping.
	 *
	 * @todo  Store errors.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $mapping_post Mapping post object.
	 *
	 * @return mixed Result of sync. WP_Error on failure.
	 */
	public function sync_items( $mapping_post ) {
		$result = $this->_sync_items( $mapping_post );
		do_action( 'gc_sync_items_result', $result, $this );
		return $result;
	}

	/**
	 * Handles syncing items for a mapping.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $mapping_post Mapping post object.
	 *
	 * @throws Exception On failure.
	 *
	 * @return mixed Result of sync.
	 */
	protected function _sync_items( $mapping_post ) {
		try {
			$this->mapping = Mapping_Post::get( $mapping_post, true );

			$this->check_mapping_data();
			$ids = $this->get_items_to_sync( $this->direction );

		} catch ( \Exception $e ) {
			if ( $this->mapping ) {
				$this->mapping->update_items_to_sync( false, $this->direction );
			}

			return new WP_Error( "gc_{$this->direction}_items_fail_" . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		$id                  = array_shift( $ids['pending'] );
		$progress_option_key = "gc_{$this->direction}_item_{$id}";
		$in_progress         = get_option( $progress_option_key );

		if ( $in_progress ) {
			return new WP_Error( "gc_{$this->direction}_item_in_progress", sprintf( __( 'Currently in progress: %d', 'gathercontent-import' ), $id ) );
		}

		try {
			update_option( $progress_option_key, time(), false );
			$result = $this->do_item( $id );
		} catch ( \Exception $e ) {
			$data = $e->get_data();
			if ( is_array( $data ) ) {
				$data['sync_item_id'] = $id;
			} else {
				$data = array( 'data' => $data, 'sync_item_id' => $id );
			}
			$result = new WP_Error( "gc_{$this->direction}_item_fail_" . $e->getCode(), $e->getMessage(), $data );
		}

		$ids['complete'] = isset( $ids['complete'] ) ? $ids['complete'] : array();
		$ids['complete'][] = $id;

		$this->mapping->update_items_to_sync( $ids, $this->direction );
		delete_option( $progress_option_key );

		// If we have more items...
		if ( ! empty( $ids['pending'] ) ) {
			// Then trigger the next async request.
			do_action( "gc_{$this->direction}_items", $this->mapping );
		} else {
			// Trigger sync complete event.
			do_action( "gc_{$this->direction}_complete", $this->mapping );
		}

		return $result;
	}

	/**
	 * Wrapper for `get_post` that throws an exception if not found.
	 *
	 * @since  3.0.0
	 *
	 * @param  int|object $post_id Post id or post object.
	 *
	 * @throws Exception On failure.
	 *
	 * @return object Post object.
	 */
	protected function get_post( $post_id ) {
		$post = $post_id instanceof WP_Post ? $post_id : get_post( $post_id );
		if ( ! $post ) {
			throw new Exception( sprintf( __( 'No post object by that id: %d', 'gathercontent-import' ), $post_id ), __LINE__ );
		}

		return $post;
	}

	/**
	 * Gets an item from the API, then sets it as the class item property.
	 *
	 * @since 3.0.0
	 *
	 * @param int $item_id Item id.
	 *
	 * @throws Exception On failure.
	 *
	 * @return object Item object.
	 */
	protected function set_item( $item_id ) {
		$this->item = $this->api->uncached()->get_item( $item_id );

		if ( ! isset( $this->item->id ) ) {
			// @todo maybe check if error was temporary.
			throw new Exception( sprintf( __( 'GatherContent could not get an item for that item id: %d', 'gathercontent-import' ), $item_id ), __LINE__, $this->item );
		}

		return $this->item;
	}

	/**
	 * Gets the pending sync items.
	 *
	 * @since 3.0.0
	 *
	 * @throws Exception On failure.
	 *
	 * @return array Array of pending sync items.
	 */
	protected function get_items_to_sync() {
		$items = $this->mapping->get_items_to_sync( $this->direction );

		if ( empty( $items['pending'] ) ) {
			throw new Exception( sprintf( __( 'No items to %s for: %s', 'gathercontent-import' ), $this->direction, $this->mapping->ID ), __LINE__ );
		}

		return $items;
	}

	/**
	 * Ensures the mapping has mapping data set.
	 *
	 * @since 3.0.0
	 *
	 * @throws Exception On failure.
	 *
	 * @return void
	 */
	protected function check_mapping_data() {
		$mapping_data = $this->mapping->data();
		if ( empty( $mapping_data ) ) {
			// @todo maybe check if error was temporary.
			throw new Exception( sprintf( __( 'No mapping data found for: %s', 'gathercontent-import' ), $this->mapping->ID ), __LINE__ );
		}
	}

	/**
	 * Gets the current element's value, and passes through a filter.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed  The current element's value.
	 */
	protected function get_element_value() {
		$val = $this->get_value_for_element( $this->element );
		return apply_filters( 'gc_get_element_value', $val, $this->element, $this->item );
	}

	/**
	 * Gets the element's value, based on the element type.
	 *
	 * @since  3.0.0
	 *
	 * @param object $element The element to get the value for.
	 *
	 * @return mixed  The current element's value.
	 */
	protected function get_value_for_element( $element ) {
		$val = false;

		switch ( $element->type ) {
			case 'text':
				$val = $element->value;
				$val = trim( str_replace( "\xE2\x80\x8B", '', $val ) );
				if ( ! $element->plain_text ) {
					$val = preg_replace_callback(
						'#\<p\>(.+?)\<\/p\>#s',
						function ( $matches ) {
							return '<p>' . str_replace( array(
								"\n    ",
								"\r\n    ",
								"\r    ",
								"\n",
								"\r\n",
								"\r",
							), '', $matches[1] ) . '</p>';
						},
						$val
					);
					$val = str_replace( '</ul><', "</ul>\n<", $val );
					$val = preg_replace( '/<\/p>\s*<p>/m', "</p>\n<p>", $val );
					$val = preg_replace( '/<\/p>\s*</m', "</p>\n<", $val );
					$val = preg_replace( '/<p>\s*<\/p>/m', '<p>&nbsp;</p>', $val );
					$val = str_replace(
						array(
							'<ul><li',
							'</li><li>',
							'</li></ul>',
						), array(
							"<ul>\n\t<li",
							"</li>\n\t<li>",
							"</li>\n</ul>",
						),
						$val
					);

					$val = preg_replace( '/<mark[^>]*>/i', '', $val );
					$val = preg_replace( '/<\/mark>/i', '', $val );

					// Replace encoded ampersands in html entities.
					// http://regexr.com/3dpcf -- example.
					$val = preg_replace_callback( '~(&amp;)(?:[a-z,A-Z,0-9]+|#\d+|#x[0-9a-f]+);~', function( $matches ) {
						return str_replace( '&amp;', '&', $matches[0] );
					}, $val );

				}
				$val = wp_kses_post( $val );
				break;

			case 'choice_radio':
				$val = '';
				foreach ( $element->options as $idx => $option ) {
					if ( $option->selected ) {
						if ( isset( $option->value ) ) {
							$val = sanitize_text_field( $option->value );
						} else {
							$val = sanitize_text_field( $option->label );
						}
					}
				}
				break;

			case 'choice_checkbox':
				$val = array();
				foreach ( $element->options as $option ) {
					if ( $option->selected ) {
						$val[] = sanitize_text_field( $option->label );
					}
				}
				break;

			case 'files':
				if ( is_array( $this->item->files ) && isset( $this->item->files[ $element->name ] ) ) {
					$val = $this->item->files[ $element->name ];
				}
				break;

			default:
				if ( isset( $element->value ) ) {
					$val = sanitize_text_field( $option->label );
				}
				break;
		}

		return $val;
	}

	/**
	 * Sets the current element's value.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function set_element_value() {
		$this->element->value = $this->get_element_value();
	}

	/**
	 * Determines if the field can be appended to.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $field Field to check.
	 *
	 * @return bool          Whether field can append.
	 */
	protected function type_can_append( $field ) {
		$can_append = in_array( $field, $this->append_types, true );

		return apply_filters( "gc_can_append_{$field}", $can_append, $this->element, $this->item );
	}

	/**
	 * Check for existence of image/media shortcodes in the GC content, and parse the attributes.
	 * `[media-$number align=left|right|center|none linkto=file|attachment-page size=thumbnail|medium|large|etc]`
	 *
	 * @since  3.0.0
	 * @uses   get_shortcode_regex
	 *
	 * @param  string $content The GC content.
	 * @param  int    $args    Args for field/image positional argument.
	 *
	 * @return false|array     Array of attributes on success.
	 */
	public function get_media_shortcode_attributes( $content, $args ) {
		$args = wp_parse_args( $args, array(
			'position'     => 1,
			'field_number' => '',
		) );
		preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );

		$suffix = $args['field_number'] > 1 ? '_' . $args['field_number'] : '';
		$to_find = array( "media{$suffix}-{$args['position']}" );
		$tagnames = array_intersect( $to_find, $matches[1] );

		if ( empty( $tagnames ) ) {
			if ( ! $suffix ) {
				$to_find = array( "media_1-{$args['position']}" );
				$tagnames = array_intersect( $to_find, $matches[1] );
				if ( empty( $tagnames ) ) {
					return false;
				}
			} else {
				return false;
			}
		}

		$pattern = get_shortcode_regex( $tagnames );

		$matches = array();
		preg_match_all( "/$pattern/", $content, $matches );

		if ( isset( $matches[3], $matches[0] ) && is_array( $matches[3] ) ) {
			$matches[3] = wp_unslash( $matches[3] ); // Fixes quoted attributes.
			$replace = array();
			foreach ( $matches[0] as $index => $shortcode ) {
				$replace[ $shortcode ] = shortcode_parse_atts( $matches[3][ $index ] );
			}

			return $replace;
		}

		return false;
	}

	/**
	 * If a GC "shortcode" is found, we'll parse the attributes and retun an image for insertion.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $atts      Array of attributes.
	 * @param  int   $media_id  The GC media object id.
	 * @param  int   $attach_id The WP media id.
	 *
	 * @return string           Image markup, if successful.
	 */
	public function get_requested_media( $atts, $media_id, $attach_id ) {
		$image = '';

		$atts = wp_parse_args( $atts, array(
			'size'   => 'full',
			'align'  => '',
			'linkto' => '',
			'class'  => '',
			'alt'    => '',
		) );

		$atts = array_map( 'esc_attr', $atts );

		if ( ! $atts['linkto'] && ! ( $atts['size'] || $atts['align'] ) ) {
			return $image;
		}

		switch ( $atts['align'] ) {
			case 'alignleft':
			case 'left':
				$alignclass = 'alignleft';
				break;
			case 'aligncenter':
			case 'center':
				$alignclass = 'aligncenter';
				break;
			case 'alignright':
			case 'right':
				$alignclass = 'alignright';
				break;
			case 'alignnone':
			case 'none':
				$alignclass = 'alignnone';
				break;
			default:
				$alignclass = $atts['align'] = '';
				break;
		}

		if ( is_array( $atts['size'] ) ) {
			$atts['size'] = array_map( 'absint', $atts['size'] );
			$size_class = join( 'x', $atts['size'] );
		} else {
			$atts['size'] = $size_class = $atts['size'];
			if ( 'full' === $atts['size'] ) {
				$atts['size'] = '';
			}
		}

		if ( $atts['linkto'] ) {
			$atts['linkto'] = 'attachment-page' === $atts['linkto'] ? $atts['linkto'] : 1;
		}

		$args = array(
			'data-gcid'   => $media_id,
			'data-gcatts' => wp_json_encode( array_filter( $atts ) ),
			'class'       => "gathercontent-image $alignclass attachment-$size_class size-$size_class wp-image-$attach_id" . ( $atts['class'] ? ' ' . $atts['class'] : '' ),
		);

		if ( ! empty( $atts['alt'] ) ) {
			$args['alt'] = $atts['alt'];
		}

		if ( $atts['linkto'] ) {
			$image = wp_get_attachment_link(
				$attach_id,
				$atts['size'] ? $atts['size'] : 'full',
				'attachment-page' === $atts['linkto'],
				false,
				false,
				$args
			);
		} elseif ( $atts['size'] || $atts['align'] ) {
			$image = wp_get_attachment_image( $attach_id, $atts['size'], false, $args );
		}

		return $image;
	}

	/**
	 * Parses content for media with data-gcid and data-gcatts attributes,
	 * and converts them to GC shortcodes. This is intended for PUSHING
	 * content to GatherContent.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $content HTML content.
	 *
	 * @return string          Updated content.
	 */
	public function convert_media_to_shortcodes( $content ) {
		$dom          = new Dom( $content );
		$images       = $dom->getElementsByTagName( 'img' );
		$replacements = array();
		$index        = 0;
		$ids          = array();

		foreach ( $images as $img ) {
			$gcid = $img->getAttribute( 'data-gcid' );
			$data = $img->getAttribute( 'data-gcatts' );
			if ( empty( $gcid ) && empty( $data ) ) {
				continue;
			}

			// It's possible GC media shortcodes could be used more than once
			// Only increase the index if the gcid (gc media id) is unique.
			if ( ! isset( $ids[ $gcid ] ) ) {
				$index++;
			}

			// Mark this gc media id.
			$ids[ $gcid ] = 1;

			$string = '';
			$node_to_replace = $dom->saveHTML( $img );

			if ( ! empty( $data ) ) {
				$data = json_decode( $data, true );
				if ( is_array( $data ) ) {
					foreach ( $data as $key => $value ) {
						$string .= " $key=$value";
					}

					// If wrapped in a link, need to get that too.
					if ( isset( $data['linkto'] ) && in_array( $data['linkto'], array( 'attachment-page', 'file' ), true ) ) {
						// @codingStandardsIgnoreStart
						if ( 'a' === $img->parentNode->tagName ) {
							$node_to_replace = $dom->saveHTML( $img->parentNode );
							// @codingStandardsIgnoreEnd
						}
					}
				}
			}

			$shortcode = "[media-$index$string]";
			$replacements[ $node_to_replace ] = $shortcode;
		}

		return strtr( $dom->get_content(), $replacements );
	}

	/**
	 * Removes faulty "zero width space", which seems to come through the GC API.
	 *
	 * @link http://stackoverflow.com/questions/11305797/remove-zero-width-space-characters-from-a-javascript-string
	 * U+200B zero width space
	 * U+200C zero width non-joiner Unicode code point
	 * U+200D zero width joiner Unicode code point
	 * U+FEFF zero width no-break space Unicode code point
	 *
	 * @param  string $string The string to clean.
	 * @return string The cleaned up string.
	 */
	public static function remove_zero_width( $string ) {
		return preg_replace( '/[\x{200B}-\x{200D}]/u', '', $string );
	}

	/**
	 * Magic getter for our object, to make protected properties accessible.
	 *
	 * @param  string $property Protected class property.
	 * @return mixed
	 */
	public function __get( $property ) {
		return $this->{$property};
	}

}
