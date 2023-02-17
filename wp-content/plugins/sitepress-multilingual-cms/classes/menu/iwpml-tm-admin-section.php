<?php

interface IWPML_TM_Admin_Section {

	/**
	 * Returns a value which will be used for sorting the sections.
	 *
	 * @return int
	 */
	public function get_order();

	/**
	 * Returns the unique slug of the sections which is used to build the URL for opening this section.
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Returns one or more capabilities required to display this section.
	 *
	 * @return string|array
	 */
	public function get_capabilities();

	/**
	 * Returns the caption to display in the section.
	 *
	 * @return string
	 */
	public function get_caption();

	/**
	 * Returns the callback responsible for rendering the content of the section.
	 *
	 * @return callable
	 */
	public function get_callback();

	/**
	 * This method is hooked to the `admin_enqueue_scripts` action.
	 *
	 * @param string $hook The current page.
	 */
	public function admin_enqueue_scripts( $hook );

	/**
	 * Used to extend the logic for displaying/hiding the section.
	 *
	 * @return bool
	 */
	public function is_visible();
}
