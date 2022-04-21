<?php

namespace WPML\TM\ATE\Review;

use WPML\FP\Relation;

class ReviewCompletedNotice implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( Relation::propEq( 'reviewCompleted', 'inWPML', $_GET ) ) {
			$text = esc_html__( "You've completed reviewing everything. WPML will let you know when there's new content to review.", 'wpml-translation-management' );
			wpml_get_admin_notices()->add_notice(
				\WPML_Notice::make( 'reviewCompleted', $text )
				            ->set_css_class_types( 'notice-info' )
				            ->set_flash()
			);
		}
	}
}
