<?php
namespace FakerPress\Module;
use FakerPress\Admin;
use FakerPress\Variable;
use FakerPress\Plugin;
use FakerPress\Utils;

class Term extends Base {

	public $dependencies = array(
		'\Faker\Provider\Lorem',
	);

	public $meta = false;

	public $provider = '\Faker\Provider\WP_Term';

	public function init() {
		$this->page = (object) array(
			'menu' => esc_attr__( 'Terms', 'fakerpress' ),
			'title' => esc_attr__( 'Generate Terms', 'fakerpress' ),
			'view' => 'terms',
		);

		add_filter( "fakerpress.module.{$this->slug}.save", array( $this, 'do_save' ), 10, 3 );
	}

	public function format_link( $id ) {
		return absint( $id );
	}

	/**
	 * Fetches all the FakerPress related Terms
	 * @return array IDs of the Terms with
	 */
	public static function fetch() {
		$terms = get_option( 'fakerpress.module_flag.term', array() );

		return $terms;
	}

	/**
	 * Use this method to prevent excluding something that was not configured by FakerPress
	 *
	 * @todo  Improve this to allow better checking
	 *
	 * @param  array $items The array of arrays, first level has taxonomy as keys, second level only ids
	 * @return bool
	 */
	public static function delete( $items ) {
		$deleted = array();
		foreach ( $items as $taxonomy => $terms ){
			$deleted[ $taxonomy ] = array();

			foreach ( $terms as $term ){
				$deleted[ $taxonomy ][ $term ] = wp_delete_term( $term, $taxonomy );
			}
		}

		delete_option( 'fakerpress.module_flag.term' );

		return $deleted;
	}

	public function do_save( $return_val, $data, $module ) {
		$args = array(
			'description' => $data['description'],
			'parent' => $data['parent_term'],
		);

		$term_object = wp_insert_term( $data['name'], $data['taxonomy'], $args );
		if ( is_wp_error( $term_object ) ) {
			return false;
		}

		$flagged = get_option( 'fakerpress.module_flag.' . $this->slug, array() );

		// Ensure that this option is an Array by reseting the variable.
		if ( ! is_array( $flagged ) ){
			$flagged = array();
		}

		if ( ! isset( $flagged[ $data['taxonomy'] ] ) || ! is_array( $flagged[ $data['taxonomy'] ] ) ){
			$flagged[ $data['taxonomy'] ] = array();
		}
		$flagged[ $data['taxonomy'] ] = array_merge( $flagged[ $data['taxonomy'] ], (array) $term_object['term_id'] );

		// When in posts relating is harder so we store in the Options Table
		update_option( 'fakerpress.module_flag.' . $this->slug, $flagged );

		return $term_object['term_id'];
	}

	public function parse_request( $qty, $request = array() ) {
		if ( is_null( $qty ) ) {
			$qty = Utils::instance()->get_qty_from_range( Variable::super( INPUT_POST, array( Plugin::$slug, 'qty' ), FILTER_UNSAFE_RAW ) );
		}

		if ( 0 === $qty ){
			return esc_attr__( 'Zero is not a good number of terms to fake...', 'fakerpress' );
		}

		$name_size = Variable::super( INPUT_POST, array( Plugin::$slug, 'size' ), FILTER_UNSAFE_RAW );

		// Fetch taxomies
		$taxonomies = Variable::super( $request, array( 'taxonomies' ), FILTER_SANITIZE_STRING );
		$taxonomies = array_map( 'trim', explode( ',', $taxonomies ) );
		$taxonomies = array_intersect( get_taxonomies( array( 'public' => true ) ), $taxonomies );

		// Only has meta after 4.4-beta
		$has_metas = version_compare( $GLOBALS['wp_version'], '4.4-beta', '>=' );

		if ( $has_metas ) {
			$metas = Variable::super( $request, array( 'meta' ), FILTER_UNSAFE_RAW );
		}

		for ( $i = 0; $i < $qty; $i++ ) {
			$this->set( 'taxonomy', $taxonomies );
			$this->set( 'name', $name_size );
			$this->set( 'description' );
			$this->set( 'parent_term' );

			$term_id = $this->generate()->save();

			if ( $has_metas && $term_id && is_numeric( $term_id ) ){
				foreach ( $metas as $meta_index => $meta ) {
					Meta::instance()->object( $term_id, 'term' )->generate( $meta['type'], $meta['name'], $meta )->save();
				}
			}

			$results[] = $term_id;
		}

		$results = array_filter( (array) $results, 'absint' );

		return $results;
	}

	public function _action_parse_request( $view ) {
		if ( 'post' !== Admin::$request_method || empty( $_POST ) ) {
			return false;
		}

		$nonce_slug = Plugin::$slug . '.request.' . Admin::$view->slug . ( isset( Admin::$view->action ) ? '.' . Admin::$view->action : '' );

		if ( ! check_admin_referer( $nonce_slug ) ) {
			return false;
		}

		// After this point we are safe to say that we have a good POST request
		$results = $this->parse_request( null, Variable::super( INPUT_POST, array( Plugin::$slug ), FILTER_UNSAFE_RAW ) );

		if ( is_string( $results ) ) {
			return Admin::add_message( $results, 'error' );
		} else {
			return Admin::add_message(
				sprintf(
					__( 'Faked %d new %s: [ %s ]', 'fakerpress' ),
					count( $results ),
					_n( 'term', 'terms', count( $results ), 'fakerpress' ),
					implode( ', ', array_map( array( $this, 'format_link' ), $results ) )
				)
			);
		}

	}
}
