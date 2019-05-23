<?php
/**
 * WPML_Action_Filter_Loader class file
 *
 * @package WPML\Core
 */

/**
 * Class WPML_Action_Filter_Loader
 */
class WPML_Action_Filter_Loader {

	/**
	 * Deferred actions
	 *
	 * @var  array $defered_actions
	 */
	private $defered_actions = array();

	/**
	 * Ajax action validation
	 *
	 * @var  WPML_AJAX_Action_Validation $ajax_action_validation
	 */
	private $ajax_action_validation;

	/**
	 * Load action filter
	 *
	 * @param string[] $loaders Action loaders.
	 */
	public function load( $loaders ) {
		foreach ( $loaders as $loader ) {
			$implementations = class_implements( $loader );

			if ( ! $implementations ) {
				continue;
			}

			$backend  = in_array( 'IWPML_Backend_Action_Loader', $implementations, true );
			$frontend = in_array( 'IWPML_Frontend_Action_Loader', $implementations, true );
			$ajax     = in_array( 'IWPML_AJAX_Action_Loader', $implementations, true );
			$rest     = in_array( 'IWPML_REST_Action_Loader', $implementations, true );
			$cli      = in_array( 'IWPML_CLI_Action_Loader', $implementations, true );

			if ( $backend && $frontend ) {
				$this->load_factory( $loader );
			} elseif ( $backend && is_admin() ) {
				$this->load_factory( $loader );
			} elseif ( $frontend && ! is_admin() ) {
				$this->load_factory( $loader );
			} elseif ( $ajax && wpml_is_ajax() ) {
				$this->load_factory( $loader );
			} elseif ( $rest && wpml_is_rest_request() ) {
				$this->load_factory( $loader );
			} elseif ( $cli && wpml_is_cli() ) {
				$this->load_factory( $loader );
			}
		}
	}

	/**
	 * Load factory
	 *
	 * @param string $loader Action loader.
	 */
	private function load_factory( $loader ) {
		/**
		 * Action loader factory
		 *
		 * @var IWPML_Action_Loader_Factory $factory
		 */
		$factory = new $loader();

		if ( $factory instanceof WPML_AJAX_Base_Factory ) {
			/**
			 * Ajax base factory
			 *
			 * @var WPML_AJAX_Base_Factory $factory
			 */
			$factory->set_ajax_action_validation( $this->get_ajax_action_validation() );
		}

		if ( $factory instanceof IWPML_Deferred_Action_Loader ) {
			$this->add_deferred_action( $factory );
		} else {
			$this->run_factory( $factory );
		}
	}

	/**
	 * Add deferred action
	 *
	 * @param IWPML_Deferred_Action_Loader $factory Action factory.
	 */
	private function add_deferred_action( IWPML_Deferred_Action_Loader $factory ) {
		$action = $factory->get_load_action();
		if ( ! isset( $this->defered_actions[ $action ] ) ) {
			$this->defered_actions[ $action ] = array();
			add_action( $action, array( $this, 'deferred_loader' ) );
		}
		$this->defered_actions[ $action ][] = $factory;
	}

	/**
	 * Deferred action loader
	 */
	public function deferred_loader() {
		$action = current_action();
		foreach ( $this->defered_actions[ $action ] as $factory ) {
			/**
			 * Deferred action loader factory
			 *
			 * @var IWPML_Deferred_Action_Loader $factory
			 */
			$this->run_factory( $factory );
		}
	}

	/**
	 * Get ajax action validation
	 *
	 * @return WPML_AJAX_Action_Validation
	 */
	private function get_ajax_action_validation() {
		if ( ! $this->ajax_action_validation ) {
			$this->ajax_action_validation = new WPML_AJAX_Action_Validation();
		}

		return $this->ajax_action_validation;
	}

	/**
	 * Run factory
	 *
	 * @param IWPML_Action_Loader_Factory $factory Action loader factory.
	 */
	private function run_factory( IWPML_Action_Loader_Factory $factory ) {
		$load_handlers = $factory->create();

		if ( $load_handlers ) {
			if ( ! is_array( $load_handlers ) ) {
				$load_handlers = array( $load_handlers );
			}
			foreach ( $load_handlers as $load_handler ) {
				$load_handler->add_hooks();
			}
		}
	}
}
