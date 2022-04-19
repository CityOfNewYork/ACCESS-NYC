<?php
namespace GatherContent\Importer\Post_Types;
use GatherContent\Importer\Base as Plugin_Base;

abstract class Base extends Plugin_Base {

	const SLUG = '';
	public $args = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct( $labels, $args ) {
		$this->args = $args;
		$this->args['labels'] = $labels;

		parent::__construct();

		if ( did_action( 'init' ) ) {
			$this->register_post_type();
		} else {
			add_action( 'init', array( $this, 'register_post_type' ) );
		}
	}

	public function register_post_type() {
		$this->args = register_post_type( static::SLUG, $this->args );

		add_filter( 'enter_title_here', array( $this, 'modify_title' ) );
	}

	/**
	 * Filter CPT title entry placeholder text
	 *
	 * @since  0.1.0
	 * @param  string $title Original placeholder text
	 * @return string        Modifed placeholder text
	 */
	public function modify_title( $title ){

		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $screen->post_type == static::SLUG ) {
			$name = $this->args->labels->singular_name;
			return sprintf( __( '%s Title', 'cpt-core' ), $name );
		}

		return $title;
	}
}
