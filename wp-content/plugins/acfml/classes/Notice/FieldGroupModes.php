<?php

namespace ACFML\Notice;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\FieldGroup;
use WPML\LIB\WP\Hooks;

class FieldGroupModes implements \IWPML_Backend_Action {

	const NOTICE_GROUP = 'acfml';
	const NOTICE_ID    = 'field-group-modes';

	public function add_hooks() {
		if( ! wp_doing_ajax() ) {
			Hooks::onAction( 'shutdown' )
			     ->then( [ $this, 'onFieldGroupsListNotice' ] );
		}
	}

	/**
	 * @return void
	 */
	public function onFieldGroupsListNotice() {
		if (
			! function_exists( 'wpml_get_admin_notices' )
			|| ! \WPML_ACF::is_acf_active()
		) {
			return;
		}
		if ( $this->hasFieldGroupMissingMode() ) {
			$this->createNotice( wpml_get_admin_notices() );
		} else {
			$this->removeNotice( wpml_get_admin_notices() );
		}
	}

	/**
	 * @param \WPML_Notices $notices
	 *
	 * @return void
	 */
	private function createNotice( $notices ) {
		$text  = ' <h2><i class="otgs-ico-wpml"></i>&nbsp; ' . esc_html__( "Let's Start Translating!", 'acfml' ) . '</h2>';
		$text .= '<p>' . esc_html__( "Edit each Field Group to select a translation option for the fields inside it. If you don't set a translation option, you will not be able to translate your fields.", 'acfml' ) . '</p>';
		$text .= '<p>' . sprintf(
			/* translators: The placeholders are replaced by an HTML link pointing to the documentation. */
			esc_html__( 'Read more about %1$show to translate your ACF custom fields%2$s', 'acfml' ),
			'<a href="' . esc_url( Links::getAcfmlMainDoc() ) . '" class="wpml-external-link" target="_blank">',
			'</a>'
		) . '</p>';

		$notice  = $notices->create_notice( self::NOTICE_ID, $text, self::NOTICE_GROUP );
		$notice->set_hideable( true );
		$notice->set_css_class_types( [ 'acfml-notice' ] );
		$notice->set_restrict_to_screen_ids( [ 'edit-acf-field-group' ] );
		$notices->add_notice( $notice );
	}

	/**
	 * @param \WPML_Notices $notices
	 *
	 * @return void
	 */
	private function removeNotice( $notices ) {
		$notices->remove_notice( self::NOTICE_GROUP, self::NOTICE_ID );
	}

	/**
	 * @return bool
	 */
	private function hasFieldGroupMissingMode() {
		return (bool) wpml_collect( acf_get_field_groups() )
			->first( function( $group ) {
				return ! isset( $group[ Mode::KEY ] );
			} );
	}
}
