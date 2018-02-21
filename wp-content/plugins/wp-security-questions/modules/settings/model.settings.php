<?php
/**
 * Class: WSQ_Model_Settings
 * @author Flipper Code <hello@flippercode.com>
 * @version 3.0.0
 * @package Maps
 */

if ( ! class_exists( 'WSQ_Model_Settings' ) ) {

	/**
	 * Setting model for Plugin Options.
	 * @package Maps
	 * @author Flipper Code <hello@flippercode.com>
	 */
	class WSQ_Model_Settings extends FlipperCode_Model_Base {
		/**
		 * Intialize Backup object.
		 */
		function __construct() {
		}
		/**
		 * Admin menu for Settings Operation
		 * @return array Admin menu navigation(s).
		 */
		function navigation() {
			return array(
				'wsq_manage_settings' => __( 'Plugin Settings', WSQ_TEXT_DOMAIN ),
			);
		}
		/**
		 * Add or Edit Operation.
		 */
		function save() {

			if ( isset( $_REQUEST['_wpnonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ); }

			if ( isset( $nonce ) and ! wp_verify_nonce( $nonce, 'wpgmp-nonce' ) ) {

				die( 'Cheating...' );

			}

			$this->verify( $_POST );

			if ( is_array( $this->errors ) and ! empty( $this->errors ) ) {
				$this->throw_errors();
			}
			$questions = array();
			if ( isset( $_POST['security_questions'] ) ) {
				foreach ( $_POST['security_questions'] as $index => $question ) {
					if ( $question != '' ) {
						$questions[$index] = sanitize_text_field( wp_unslash( $question ) );
					}
				}
			}
			$settings = wp_unslash($_POST);
			update_option( 'wpr_register_security_ques',  $questions );
			update_option( 'wpr_security_ques_setting',$settings );
			$response['success'] = __( 'Setting(s) saved successfully.',WSQ_TEXT_DOMAIN );
			return $response;

		}
	}
}
