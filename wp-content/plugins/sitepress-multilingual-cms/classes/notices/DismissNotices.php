<?php

namespace WPML\Notices;

class DismissNotices implements \IWPML_Backend_Action {

	const OPTION    = 'wpml_dismiss_notice';
	const CSS_CLASS = 'wpml_dismiss_notice';

	public function add_hooks() {
		add_action( 'wp_ajax_wpml_dismiss_notice', [ $this, 'toggleDismiss' ] );

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_script(
					'wpml-dismiss-notice',
					ICL_PLUGIN_URL . '/dist/js/notices/app.js',
					[],
					ICL_SITEPRESS_VERSION
				);
			}
		);
	}

	public function toggleDismiss() {
		$postData = wpml_collect( $_POST );
		$id       = $postData->get( 'id', null );
		if ( ! $id ) {
			return wp_send_json_error( 'ID of notice is not defined' );
		}

		$options        = get_option( self::OPTION, [] );
		$options[ $id ] = $postData->get( 'dismiss', false ) === 'true';

		update_option( self::OPTION, $options );

		return wp_send_json_success();

	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function isDismissed( $id ) {
		return wpml_collect( get_option( self::OPTION, [] ) )->get( $id, false );
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function renderCheckbox( $id ) {
		return sprintf(
			'<input type="checkbox" class="%s" data-id="%s" />',
			self::CSS_CLASS,
			$id
		);
	}
}
