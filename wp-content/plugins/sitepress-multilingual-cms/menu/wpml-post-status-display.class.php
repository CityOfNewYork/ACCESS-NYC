<?php

class WPML_Post_Status_Display {
	const ICON_TRANSLATION_EDIT          = 'otgs-ico-edit';
	const ICON_TRANSLATION_NEEDS_UPDATE  = 'otgs-ico-refresh';
	const ICON_TRANSLATION_ADD           = 'otgs-ico-add';
	const ICON_TRANSLATION_ADD_DISABLED  = 'otgs-ico-add-disabled';
	const ICON_TRANSLATION_EDIT_DISABLED = 'otgs-ico-edit-disabled';
	const ICON_TRANSLATION_IN_PROGRESS   = 'otgs-ico-in-progress';

	private $active_langs;

	public function __construct( $active_languages ) {
		$this->active_langs = $active_languages;
	}

	/**
	 * Returns the html of a status icon.
	 *
	 * @param string $link Link the status icon is to point to.
	 * @param string $text Hover text for the status icon.
	 * @param string $img Name of the icon image file to be used.
	 * @param string $css_class
	 *
	 * @return string
	 */
	private function render_status_icon( $link, $text, $css_class ) {

		if ( $link ) {
			$icon_html = '<a href="' . esc_url( $link ) . '" class="js-wpml-translate-link">';
		} else {
			$icon_html = '<a>';
		}
		$icon_html .= $this->get_action_icon( $css_class, $text );
		$icon_html .= '</a>';

		return $icon_html;
	}

	private function get_action_icon( $css_class, $label ) {
		return '<i class="' . $css_class . ' js-otgs-popover-tooltip" title="' . esc_attr( $label ) . '"></i>';
	}

	/**
	 * This function takes a post ID and a language as input.
	 * It will always return the status icon,
	 * of the version of the input post ID in the language given as the second parameter.
	 *
	 * @param int    $post_id  original post ID
	 * @param string $lang     language of the translation
	 *
	 * @return string
	 */
	public function get_status_html( $post_id, $lang ) {
		list( $text, $link, $trid, $css_class ) = $this->get_status_data( $post_id, $lang );
		if ( ! did_action( 'wpml_pre_status_icon_display' ) ) {
			do_action( 'wpml_pre_status_icon_display' );
		}

		/**
		 * Filters the translation edit link.
		 *
		 * @param string $link
		 * @param int    $post_id
		 * @param string $lang
		 * @param int    $trid
		 * @param string $css_class
		 */
		$link = apply_filters( 'wpml_link_to_translation', $link, $post_id, $lang, $trid, $css_class );

		/**
		 * Filters the translation status text.
		 *
		 * @param string $text
		 * @param int    $post_id
		 * @param string $lang
		 * @param int    $trid
		 * @param string $css_class
		 */
		$text = apply_filters( 'wpml_text_to_translation', $text, $post_id, $lang, $trid, $css_class );

		/**
		 * Filter the CSS class for the status icon.
		 *
		 * @since 4.2.0
		 *
		 * @param string $css_class
		 * @param int    $post_id
		 * @param string $lang
		 * @param int    $trid
		 */
		$css_class = apply_filters( 'wpml_css_class_to_translation', $css_class, $post_id, $lang, $trid );

		$css_class = $this->map_old_icon_filter_to_css_class( $css_class, $post_id, $lang, $trid );

		/**
		 * Filter the HTML link to edit the translation
		 *
		 * @since 4.2.0
		 *
		 * @param string HTML link
		 * @param int    $post_id
		 * @param string $lang
		 * @param int    $trid
		 */
		return apply_filters(
			'wpml_post_status_display_html',
			$this->render_status_icon( $link, $text, $css_class ),
			$post_id,
			$lang,
			$trid
		);
	}

	/**
	 * @param string $css_class
	 * @param int    $post_id
	 * @param string $lang
	 * @param int    $trid
	 *
	 * @return string
	 */
	private function map_old_icon_filter_to_css_class( $css_class, $post_id, $lang, $trid ) {
		$map = array(
			'edit_translation.png'          => self::ICON_TRANSLATION_EDIT,
			'needs-update.png'              => self::ICON_TRANSLATION_NEEDS_UPDATE,
			'add_translation.png'           => self::ICON_TRANSLATION_ADD,
			'in_progress.png'               => self::ICON_TRANSLATION_IN_PROGRESS,
			'add_translation_disabled.png'  => self::ICON_TRANSLATION_ADD_DISABLED,
			'edit_translation_disabled.png' => self::ICON_TRANSLATION_EDIT_DISABLED,
		);

		$old_icon = array_search( $css_class, $map, true );

		/**
		 * Filters the old icon image
		 *
		 * @deprecated since 4.2.0, use `wpml_css_class_to_translation` instead
		 *
		 * @param string $old_icon
		 * @param int    $post_id
		 * @param string $lang
		 * @param int    $trid
		 * @param string $css_class
		 */
		$old_icon = apply_filters( 'wpml_icon_to_translation', $old_icon, $post_id, $lang, $trid, $css_class );

		if ( $old_icon && array_key_exists( $old_icon, $map ) ) {
			$css_class = $map[ $old_icon ];
		}

		return $css_class;
	}

	public function get_status_data( $post_id, $lang ) {
		global $wpml_post_translations;

		$status_helper        = wpml_get_post_status_helper();
		$trid                 = $wpml_post_translations->get_element_trid( $post_id );
		$status               = $status_helper->get_status( false, $trid, $lang );
		$source_language_code = $wpml_post_translations->get_element_lang_code( $post_id );
		$correct_id           = $wpml_post_translations->element_id_in( $post_id, $lang );

		if ( $status && $correct_id ) {
			list( $text, $link, $css_class ) = $this->generate_edit_allowed_data( $correct_id, $status_helper->needs_update( $correct_id ) );
		} else {
			list( $text, $link, $css_class ) = $this->generate_add_data( $trid, $lang, $source_language_code, $post_id );
		}

		return array( $text, $link, $trid, $css_class );
	}

	/**
	 * @param      $post_id  int
	 * @param bool $update   true if the translation in questions is in need of an update,
	 *                       false otherwise.
	 *
	 * @return array
	 */
	private function generate_edit_allowed_data( $post_id, $update = false ) {
		global $wpml_post_translations;

		$lang_code    = $wpml_post_translations->get_element_lang_code( $post_id );
		$post_type    = $wpml_post_translations->get_type( $post_id );

		$css_class = self::ICON_TRANSLATION_EDIT;
		if ( $update && ! $wpml_post_translations->is_a_duplicate( $post_id ) ) {
			$css_class = self::ICON_TRANSLATION_NEEDS_UPDATE;
		}

		if ( $update ) {
			$text = __( 'Update %s translation', 'sitepress' );
		} else {
			$text = __( 'Edit the %s translation', 'sitepress' );
		}

		$text = sprintf( $text, $this->active_langs[ $lang_code ]['display_name'] );

		$link = 'post.php?' . http_build_query (
				array( 'lang'      => $lang_code,
				       'action'    => 'edit',
				       'post_type' => $post_type,
				       'post'      => $post_id
				)
			);

		return array( $text, $link, $css_class );
	}

	/**
	 * Generates the data for displaying a link element pointing towards a translation, that the current user can
	 * create.
	 *
	 * @param int    $trid
	 * @param int    $original_id
	 * @param string $lang_code
	 * @param string $source_language
	 *
	 * @return array
	 */
	private function generate_add_data( $trid, $lang_code, $source_language, $original_id ) {
		$link = 'post-new.php?' . http_build_query (
				array(
					'lang'        => $lang_code,
					'post_type'   => get_post_type ( $original_id ),
					'trid'        => $trid,
					'source_lang' => $source_language
				)
			);

		return array(
			sprintf( __( 'Add translation to %s', 'sitepress' ), $this->active_langs[ $lang_code ]['display_name'] ),
			$link,
			self::ICON_TRANSLATION_ADD,
		);
	}
}
