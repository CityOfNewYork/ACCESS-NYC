<?php
class TranslationProxy_Popup {

	public static function display() {
		include_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy-popup.php';
		exit;
	}

	public static function get_link( $link, $args = array(), $just_url = false ) {

		$defaults = array(
			'title'     => null,
			'class' => '',
			'id' => '',
			'ar' => 0, // auto_resize
			'unload_cb' => false, // onunload callback
		);

		$args = array_merge($defaults, $args);

		/**
		 * @var title string
		 */
		$title = $args['title'];
		/**
		 * @var $class string
		 */
		$class = $args['class'];
		/**
		 * @var $id int
		 */
		$id = $args['id'];
		/**
		 * @var $ar int
		 */
		$ar = $args['ar'];
		/**
		 * @var $unload_cb bool
		 */
		$unload_cb = $args['unload_cb'];

		if ( !empty( $ar ) ) {
			$auto_resize = '&amp;auto_resize=1';
		} else {
			$auto_resize = '';
		}

		$unload_cb = isset( $unload_cb ) ? '&amp;unload_cb=' . $unload_cb : '';

		$url_glue = false !== strpos( $link, '?' ) ? '&' : '?';
		$link     .= $url_glue . 'compact=1';

		$nonce_snippet    = '&amp;_icl_nonce=' . wp_create_nonce( 'reminder_popup_nonce' );
		$action_and_nonce = 'admin.php?page=' . ICL_PLUGIN_FOLDER
		                    . "/menu/languages.php&amp;icl_action=reminder_popup{$nonce_snippet}{$auto_resize}{$unload_cb}"
		                    . "&amp;target=" . urlencode( $link );
		if ( ! empty( $id ) ) {
			$id = ' id="' . $id . '"';
		}
		if ( ! $just_url ) {
			return '<a class="icl_thickbox ' . $class . '" title="' . $title . '" href="' . $action_and_nonce . '"' . $id . '>';
		} else {
			if ( ! $just_url ) {
				return '<a class="icl_thickbox ' . $class . '" href="' . $action_and_nonce . '"' . $id . '>';
			} else {
				return $action_and_nonce;
			}
		}
	}

}


