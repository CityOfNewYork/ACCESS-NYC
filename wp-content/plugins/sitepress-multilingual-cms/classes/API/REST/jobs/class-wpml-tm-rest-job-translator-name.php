<?php

class WPML_TM_Rest_Job_Translator_Name {

	public function get( $translator_id ) {
		$user = get_user_by( 'id', $translator_id );
		if ( $user instanceof WP_User ) {
			return $user->display_name;
		} else {
			return '';
		}
	}

}
