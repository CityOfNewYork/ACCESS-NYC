<?php

namespace WPML\PB\Compatibility\Toolset\Layouts;

use IWPML_Action;

class Hooks implements IWPML_Action {

	public function add_hooks() {
		add_filter( 'wpml_pb_is_page_builder_page', [ __CLASS__, 'isLayoutPage' ], 10, 2 );
	}

	/**
	 * @see Toolset_User_Editors_Editor_Layouts::LAYOUTS_BUILDER_OPTION_NAME
	 * @see Toolset_User_Editors_Editor_Layouts::LAYOUTS_BUILDER_OPTION_VALUE
	 *
	 * @param bool     $isPbPage
	 * @param \WP_Post $post
	 */
	public static function isLayoutPage( $isPbPage, \WP_Post $post ) {
		if ( 'yes' === get_post_meta( $post->ID, '_private_layouts_template_in_use', true ) ) {
			return true;
		}

		return $isPbPage;
	}
}