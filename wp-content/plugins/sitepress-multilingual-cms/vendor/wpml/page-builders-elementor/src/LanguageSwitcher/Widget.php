<?php
namespace WPML\PB\Elementor\LanguageSwitcher;

use Elementor\Widget_Base;

class Widget extends Widget_Base {

	/** @var WidgetAdaptor $adaptor */
	private $adaptor;

	public function __construct( $data = [], $args = null, WidgetAdaptor $adaptor = null ) {
		$this->adaptor = $adaptor ?: new WidgetAdaptor();
		$this->adaptor->setTarget( $this );
		parent::__construct( $data, $args );

	}

	/** @return string */
	public function get_name() {
		return $this->adaptor->getName();
	}

	/** @return string */
	public function get_title() {
		return $this->adaptor->getTitle();
	}

	/** @return string */
	public function get_icon() {
		return $this->adaptor->getIcon();
	}

	/** @return array */
	public function get_categories() {
		return $this->adaptor->getCategories();
	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type. For example, external
	 * developers use this method to register controls in a widget.
	 *
	 * Should be inherited and register new controls using `add_control()`,
	 * `add_responsive_control()` and `add_group_control()`, inside control
	 * wrappers like `start_controls_section()`, `start_controls_tabs()` and
	 * `start_controls_tab()`.
	 */
	protected function _register_controls() {
		$this->adaptor->registerControls();
	}

	/**
	 * Render element.
	 *
	 * Generates the final HTML on the frontend.
	 */
	protected function render() {
		$this->adaptor->render();
	}
}