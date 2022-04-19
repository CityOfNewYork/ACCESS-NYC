<?php
namespace GatherContent\Importer\Settings;
use GatherContent\Importer\Base as Base;

class Form_Section extends Base {

	protected $page;
	protected $id;
	public $field;
	public $title = '';
	public $callback = null;
	protected $fields = array();
	protected static $sections;

	public function __construct( $id, $title, $callback, $page, $is_current = false ) {
		$this->page = $page;

		$section = compact( 'id', 'title', 'callback', 'is_current' );
		$section = apply_filters( "gathercontent_importer_section_{$id}", $section, $this );

		$this->id = $section['id'];
		$this->title = $section['title'];
		$this->callback = $section['callback'];

		self::$sections[ $this->page ][ $this->id ] = $this;
	}

	public function get_section( $show ) {
		$class = 'gc-section-'. $this->id . ( $show ? '' : ' hidden' );
		$html  = '<div class="gc-setting-section '. $class .'">';

			if ( $this->title ) {
				$html .= "<h2>{$this->title}</h2>\n";
			}

			if ( $this->callback ) {
				$html .= $this->do_desc_callback();
			}

			$html .= '<table class="form-table">';
			$html .= $this->do_fields();
			$html .= '</table>';

		$html .= '</div>';

		return $html;
	}

	public function do_desc_callback() {
		if ( is_callable( $this->callback ) ) {
			ob_start();
			call_user_func( $this->callback, $this );
			return ob_get_clean();
		}

		return $this->callback;
	}

	public function do_fields() {
		if ( empty( $this->fields ) ) {
			return '';
		}

		ob_start();
		foreach ( $this->fields as $this->field ) {
			$field = $this->field;
			$class = '';

			if ( ! empty( $field['args']['class'] ) ) {
				$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
			}

			echo "<tr{$class}>";

			if ( ! empty( $field['args']['label_for'] ) ) {
				echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
			} elseif ( ! empty( $field['title'] ) ) {
				echo '<th scope="row">' . $field['title'] . '</th>';
			}

			echo '<td>';
			call_user_func( $field['callback'], $this );
			echo '</td>';
			echo '</tr>';
		}

		// Kill empty label cells
		return str_replace( '<th scope="row"></th>', '', ob_get_clean() );
	}

	public function add_field( $id, $title, $callback, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'label_for' => $title ? $id : '',
			'class' => $id . '-row',
		) );

		$field = compact( 'id', 'title', 'callback', 'args' );
		$field = apply_filters( "gathercontent_importer_field_{$this->id}_{$id}", $field, $this );
		$this->fields[ $field['id'] ] = $field;

	}

	public function do_param( $key ) {
		echo $this->param( $key );
	}

	public function param( $key ) {
		return isset( $this->field[ $key ] ) ? $this->field[ $key ] : null;
	}

	public static function get_sections( $page ) {

		$html = '';
		foreach ( self::$sections[ $page ] as $section ) {
			$html .= $section->get_section( 1 );
		}

		return $html;
	}

}


