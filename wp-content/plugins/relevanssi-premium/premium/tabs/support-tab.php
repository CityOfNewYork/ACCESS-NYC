<?php
/**
 * /premium/tabs/support-tab.php
 *
 * Prints out the Premium Support tab in Relevanssi settings.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Premium Support tab in Relevanssi settings.
 */
function relevanssi_support_tab() {
	global $relevanssi_variables;

	if ( isset( $_REQUEST['relevanssi_support_form'] ) ) {
		check_admin_referer( 'relevanssi_support_form', 'relevanssi_support_form' );
		relevanssi_support_send_email( $_REQUEST );
	}
	$support_email = $relevanssi_variables['autoupdate']->get_remote_license();

	?>
<h2 id="options"><?php esc_html_e( 'Support', 'relevanssi' ); ?></h2>
	<?php
	if ( ! $support_email ) {
		?>
<p><?php echo relevanssi_get_api_key_notification(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php
	} else {
		?>
<p>
		<?php
		printf(
			// Translators: %1$s opens the link to the support form, %2$s closes the link, %3$s is the support email address.
			esc_html__( 'This form sends out an email to the Relevanssi support. If you have a question, please fill in the form below and we will get back to you as soon as possible. If you don\'t hear from us in a day or two, it\'s possible your email has gone astray. In that case, please contact us again and use %1$sthe support form at Relevanssi.com%2$s. You can also email us directly at %3$s. Expect slower response times during June and July.', 'relevanssi' ),
			'<a href="https://www.relevanssi.com/support/">',
			'</a>',
			'<em>' . esc_html( $support_email ) . '</em>'
		);
		?>
</p>

<p>
		<?php
		// Translators: %1$s opens the link to the knowledge base, %2$s closes the link.
		printf( esc_html__( 'We have a large knowledge base. %1$sTake a look there%2$s, perhaps your question is already answered there.', 'relevanssi' ), '<a href="https://www.relevanssi.com/category/knowledge-base/">', '</a>' );
		?>
</p>

<p><?php esc_html_e( 'Instead of telling us "X doesn\'t work", please try be as specific as possible. Please tell us what you expect to happen and what actually happens.', 'relevanssi' ); ?></p>

<p><?php esc_html_e( 'Feel free to ask questions in English or Finnish. Please do not send us video questions.', 'relevanssi' ); ?></p>

<form method="post">
		<?php wp_nonce_field( 'relevanssi_support_form', 'relevanssi_support_form' ); ?>
		<input type="hidden" name="relevanssi_support_to_email" value="<?php echo esc_attr( $support_email ); ?>" />
		<table class="form-table">
			<tr>
				<th scope="row"><label for="relevanssi_support_email"><?php esc_html_e( 'Your email address', 'relevanssi' ); ?></label></th>
				<td><input type="text" name="relevanssi_support_email" id="relevanssi_support_email" value="" size="40" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="relevanssi_support_subject"><?php esc_html_e( 'Subject', 'relevanssi' ); ?></label></th>
				<td><input type="text" name="relevanssi_support_subject" id="relevanssi_support_subject" value="" size="40" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="relevanssi_support_message"><?php esc_html_e( 'Message', 'relevanssi' ); ?></label></th>
				<td><textarea name="relevanssi_support_message" id="relevanssi_support_message" rows="10" cols="50"></textarea></td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td><input type="submit" name="relevanssi_support_submit" id="relevanssi_support_submit" value="<?php esc_attr_e( 'Send', 'relevanssi' ); ?>" class="button button-primary" /></td>
			</tr>
		</table>
</form>
		<?php
	}
	?>

	<?php
}

/**
 * Sends out an email to Relevanssi support.
 *
 * @param array $request The request array.
 */
function relevanssi_support_send_email( $request ) {
	global $wp_version, $relevanssi_variables;

	$message = $request['relevanssi_support_message'];
	$from    = 'From: ' . $request['relevanssi_support_email'];
	$to      = $request['relevanssi_support_to_email'];
	$subject = $request['relevanssi_support_subject'];

	$message_intro  = 'WP version: ' . $wp_version . "\n";
	$message_intro .= 'PHP version: ' . phpversion() . "\n";
	$message_intro .= 'Relevanssi version: ' . $relevanssi_variables['plugin_version'] . "\n";
	$message_intro .= "\n";

	$message = $message_intro . stripslashes( $message );

	$success = wp_mail( $to, $subject, $message, array( $from ) );

	if ( $success ) {
		?>
<div id="message" class="updated fade">
	<p><?php esc_html_e( 'Email sent!', 'relevanssi' ); ?></p>
</div>
		<?php
	} else {
		?>
<div id="message" class="error">
	<p><?php esc_html_e( 'Email failed!', 'relevanssi' ); ?></p>
</div>
		<?php
	}
}
