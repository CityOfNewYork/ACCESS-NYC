<?php

class WPML_Admin_Table_Sort {

	/** @var  string $primary_column */
	private $primary_column;

	/** @var  string $url_args */
	private $url_args;

	/** @var  string $current_url */
	private $current_url;

	/**
	 * @param string $primary_column
	 */
	public function set_primary_column( $primary_column ) {
		$this->primary_column = $primary_column;
	}

	/**
	 * @param string $column
	 *
	 * @return string
	 */
	public function get_column_url( $column ) {
		$query_args = array(
			'orderby' => $column,
			'order'   => 'desc',
		);

		if ( $this->get_current_orderby() === $column && $this->get_current_order() === 'desc' ) {
			$query_args['order'] = 'asc';
		}

		return add_query_arg( $query_args, $this->get_current_url() );
	}

	/**
	 * @param string $column
	 *
	 * @return string
	 */
	public function get_column_classes( $column ) {
		$classes = 'manage-column column-' . $column;

		if ( $this->is_primary( $column ) ) {
			$classes .= ' column-primary';
		}

		if ( $this->get_current_orderby() === $column ) {
			$classes .= ' sorted ' . $this->get_current_order();
		} else {
			$classes .= ' sortable asc';
		}

		return $classes;
	}

	/**
	 * @param string $column
	 *
	 * @return bool
	 */
	private function is_primary( $column ) {
		return $this->primary_column === $column;
	}

	/**
	 * @return string|null
	 */
	private function get_current_orderby() {
		$url_args = $this->get_url_args();
		return isset( $url_args['orderby'] ) ? $url_args['orderby'] : null;
	}

	/**
	 * @return string|null
	 */
	private function get_current_order() {
		$url_args = $this->get_url_args();
		return isset( $url_args['order'] ) ? $url_args['order'] : null;
	}

	/**
	 * @return array
	 */
	public function get_current_sorters() {
		return array(
			'orderby' => $this->get_current_orderby(),
			'order'   => $this->get_current_order(),
		);
	}

	/**
	 * @return array
	 */
	private function get_url_args() {
		if ( ! $this->url_args ) {
			$this->url_args = array();
			$url_query  = wpml_parse_url( $this->get_current_url(), PHP_URL_QUERY );
			parse_str( $url_query, $this->url_args );
		}

		return $this->url_args;
	}

	/**
	 * @return string
	 */
	private function get_current_url() {
		if ( ! $this->current_url ) {
			$this->current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$this->current_url = remove_query_arg( 'paged', $this->current_url );
		}

		return $this->current_url;
	}
}