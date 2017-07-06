<?php

/**
 * Class WPML_Compatibility_Plugin_Visual_Composer
 *
 * @author OnTheGoSystems
 */
class WPML_Compatibility_Plugin_Visual_Composer {

	/** @var WPML_Debug_BackTrace $debug_backtrace */
	private $debug_backtrace;

	/** @var array $filters_to_restore */
	private $filters_to_restore = array();

	/**
	 * WPML_Compatibility_Plugin_Visual_Composer constructor.
	 *
	 * @param WPML_Debug_BackTrace $debug_backtrace
	 */
	public function __construct( WPML_Debug_BackTrace $debug_backtrace ) {
		$this->debug_backtrace = $debug_backtrace;
	}

	public function add_hooks() {
		$this->prevent_registering_widget_strings_twice();
	}

	private function prevent_registering_widget_strings_twice() {
		add_filter( 'widget_title', array( $this, 'suspend_vc_widget_translation' ), - PHP_INT_MAX );
		add_filter( 'widget_text', array( $this, 'suspend_vc_widget_translation' ), - PHP_INT_MAX );
		add_filter( 'widget_title', array( $this, 'restore_widget_translation' ), PHP_INT_MAX );
		add_filter( 'widget_text', array( $this, 'restore_widget_translation' ), PHP_INT_MAX );
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public function suspend_vc_widget_translation( $text ) {
		if ( $this->debug_backtrace->is_function_in_call_stack( 'vc_do_shortcode' ) ) {
			$filter           = new stdClass();
			$filter->hook     = current_filter();
			$filter->name     = 'icl_sw_filters_' . $filter->hook;
			$filter->priority = has_filter( $filter->hook, $filter->name );

			if ( false !== $filter->priority ) {
				remove_filter( $filter->hook, $filter->name, $filter->priority );
				$this->filters_to_restore[] = $filter;
			}
		}

		return $text;
	}

	/**
	 * @param string $text
	 *
	 * @return mixed
	 */
	public function restore_widget_translation( $text ) {
		foreach ( $this->filters_to_restore as $filter ) {
			add_filter( $filter->hook, $filter->name, $filter->priority );
		}

		return $text;
	}
}
