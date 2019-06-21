<?php

class WPML_Elementor_WooCommerce_Hooks {

	public function add_hooks() {
		add_filter( 'pre_get_posts', array( $this, 'do_not_suppress_filters_on_product_widget' ) );
	}

	/**
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public function do_not_suppress_filters_on_product_widget( WP_Query $query ) {
		if (
			array_key_exists( 'post_type', $query->query_vars ) && 'product' === $query->query_vars['post_type']
			&& isset( $_POST['action'] ) && 'elementor_ajax' === $_POST['action']
		) {
			$query->query_vars['suppress_filters'] = false;
		}

		return $query;
	}
}
