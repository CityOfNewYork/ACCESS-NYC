<?php

namespace Gravity_Forms\Gravity_SMTP\Handler\External;

class Gravity_Forms_Note_Handler {

	public function store_id( $id, $mail_handler ) {
		$mail_handler->set_entry_id( $id );
	}

	public function get_modified_entry_note( $og_text, $entry_id, $mail_handler, $model ) {
		$stored_entry_id = $mail_handler->get_entry_id();

		if ( (int) $stored_entry_id !== (int) $entry_id ) {
			return $og_text;
		}

		$email_id = $model->get_latest_id();

		if ( empty( $email_id ) ) {
			return $og_text;
		}

		$opening_tag = sprintf( '<a href="%s" target="_blank" style="flex: 0 0 100%%">', admin_url( 'admin.php?page=gravitysmtp-activity-log&tab=log-details&event_id=' . $email_id ) );

		// @translators - the first %s represents an opening <a> tag, while the second represents a closing </a> tag.
		$smtp_note = __( ' Email sent via Gravity SMTP. %sView Email%s', 'gravitysmtp' );
		$smtp_note = sprintf( $smtp_note, $opening_tag, '</a>' );

		$new_text = sprintf( '<div><div>%s</div><div>%s</div></div>', $og_text, $smtp_note );

		return $new_text;
	}

}