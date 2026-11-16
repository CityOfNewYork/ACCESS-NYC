<?php
/**
 * Format email to user id strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_Email_To_User_Id_Strategy class
 */
class Format_Email_To_User_Id_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_email_to_user_id';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception Format_Email_To_User_Id_Strategy->format: the value should be an email.
	 * @throws \Exception Format_Email_To_User_Id_Strategy->format: no user found for the email...
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => $value,
				'messages'        => array(),
			);
		}
		if ( ! is_string( $value ) || ! is_email( $value ) ) {
			throw new \Exception( esc_html( 'Format_Email_To_User_Id_Strategy->format: the value should be an email.' ), 400 );
		}
		$user = get_user_by( 'email', $value );
		if ( ! is_a( $user, \WP_User::class ) ) {
			throw new \Exception( esc_html( sprintf( 'Format_Email_To_User_Id_Strategy->format: no user found for the email "%s".', $value ) ), 400 );
		}
		return array(
			'success'         => true,
			'formatted_value' => $user->ID,
			'messages'        => array(),
		);
	}
}
