<?php
namespace GatherContent\Importer\Views;

class View {

	/**
	 * Array of arguments for template
	 *
	 * @var array
	 * @since  3.0.0
	 */
	public $args = array();

	/**
	 * Template name (name of file in includes/templates)
	 *
	 * @var string
	 * @since  3.0.0
	 */
	public $template = '';

	/**
	 * Cached views.
	 *
	 * @var array
	 * @since  3.0.0
	 */
	protected static $views = array();

	/**
	 * Render an HTML view with the given arguments and return the view's contents.
	 *
	 * @param string  $template The template file name, relative to the includes/templates/ folder - with or without .php extension
	 * @param array   $args     An array of arguments to extract as variables into the template
	 *
	 * @return void
	 */
	public function __construct( $template, array $args = array() ) {
		if ( empty( $template ) ) {
			throw new Exception( 'Template variable required for '. __CLASS__ .'.' );
		}

		$this->template = $template;
		$this->args = $args;
	}

	/**
	 * Loads the view and outputs it
	 *
	 * @since  3.0.0
	 *
	 * @param  boolean $echo Whether to output or return the template
	 *
	 * @return string        Rendered template
	 */
	public function load( $echo = true ) {

		// Filter args before outputting template.
		$this->args = apply_filters( "gc_template_args_for_{$this->template}", $this->args, $this );
		$id = md5( $this->template . serialize( $this->args ) );

		if ( ! isset( self::$views[ $id ] ) ) {
			try {
				ob_start();
				// Do html
				$this->_include();
				// grab the data from the output buffer and add it to our $content variable
				self::$views[ $id ] = ob_get_clean();
			} catch ( \Exception $e ) {
				wpdie( $e->getMessage() );
			}
		}

		if ( $echo ) {
			echo self::$views[ $id ];
		}

		return self::$views[ $id ];
	}

	protected function _include() {
		include GATHERCONTENT_INC . 'views/' . $this->template . '.php';
	}

	public function get( $arg, $default = null ) {
		if ( isset( $this->args[ $arg ] ) ) {
			return $this->args[ $arg ];
		}

		return $default;
	}

	public function get_from( $array_arg_name, $array_key, $default = null ) {
		if ( ! isset( $this->args[ $array_arg_name ] ) ) {
			return $default;
		}

		$array = $this->args[ $array_arg_name ];

		if ( ! is_array( $array ) || ! isset( $array[ $array_key ] ) ) {
			return $default;
		}

		return $array[ $array_key ];
	}

	/**
	 * Output one of the $args values.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $arg     The $args key.
	 * @param  mixed   $esc_cb  An escaping function callback.
	 * @param  mixed   $default Mixed value.
	 *
	 * @return mixed            Value or default.
	 */
	public function output( $arg, $esc_cb = '', $default = null ) {
		$val = $this->get( $arg, $default );

		echo $esc_cb ? $esc_cb( $val ) : $val;
	}

	public function output_from( $arg, $array_key, $esc_cb = '', $default = null ) {
		$val = $this->get_from( $array_arg_name, $array_key, $default );

		echo $esc_cb ? $esc_cb( $val ) : $val;
	}

	public function __toString() {
		return $this->load( false );
	}

}
