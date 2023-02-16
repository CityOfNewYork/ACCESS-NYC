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
			$loader_type = new WPML\Action\Type( $loader );

			$backend  = $loader_type->is( 'backend' );
			$frontend = $loader_type->is( 'frontend' );
			$ajax     = $loader_type->is( 'ajax' );
			$rest     = $loader_type->is( 'rest' );
			$cli      = $loader_type->is( 'cli' );
			$dic      = $loader_type->is( 'dic' );

			if ( $backend && $frontend ) {
				$this->load_factory_or_action( $loader, $dic );
			} elseif ( $backend && is_admin() ) {
				$this->load_factory_or_action( $loader, $dic );
			} elseif ( $frontend && ! is_admin() ) {
				$this->load_factory_or_action( $loader, $dic );
			} elseif ( $ajax && wpml_is_ajax() ) {
				$this->load_factory_or_action( $loader, $dic );
			} elseif ( $rest ) {
				$this->load_factory_or_action( $loader, $dic );
			} elseif ( $cli && wpml_is_cli() ) {
				$this->load_factory_or_action( $loader, $dic );
			}
		}
	}

	/**
	 * Load factory
	 *
	 * @param string $loader Action loader.
	 * @param bool   $use_dic
	 */
	private function load_factory_or_action( $loader, $use_dic ) {
		if ( $use_dic ) {
			$action_or_factory = WPML\Container\make( $loader );
		} else {
			$action_or_factory = new $loader();
		}

		if ( $action_or_factory instanceof IWPML_Action ) {
			$action_or_factory->add_hooks();
		} else {
			$this->load_factory( $action_or_factory );
		}
	}

	/**
	 * @param IWPML_Action_Loader_Factory $factory
	 */
	private function load_factory( IWPML_Action_Loader_Factory $factory ) {
		if ( $factory instanceof WPML_AJAX_Base_Factory ) {
			/** @var WPML_AJAX_Base_Factory $factory */
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
			if ( ! is_array( $load_handlers ) || is_callable( $load_handlers ) ) {
				$load_handlers = array( $load_handlers );
			}
			foreach ( $load_handlers as $load_handler ) {
				if ( is_callable( $load_handler ) ) {
					$load_handler();
				} else {
					$load_handler->add_hooks();
				}
			}
		}
	}
}
