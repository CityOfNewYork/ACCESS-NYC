<?php

class WPML_Admin_Pagination_Factory {

	/**
	 * @var int
	 */
	private $total_items;

	/**
	 * @var int
	 */
	private $items_per_page;

	public function __construct( $total_items, $items_per_page ) {
		$this->total_items = $total_items;
		$this->items_per_page = $items_per_page;
	}

	/**
	 * @return WPML_Admin_Pagination_Render
	 */
	public function create() {
		$pagination = new WPML_Admin_Pagination();
		$pagination->set_total_items( $this->total_items );
		$pagination->set_items_per_page( $this->items_per_page );
		$pagination->set_current_page(
			isset( $_GET['paged'] )
				? filter_var( $_GET['paged'], FILTER_SANITIZE_NUMBER_INT )
				: 1 );

		$template = new WPML_Twig_Template_Loader(
			array(
				WPML_PLUGIN_PATH . '/templates/pagination'
			)
		);

		return new WPML_Admin_Pagination_Render( $template->get_template(), $pagination );
	}
}