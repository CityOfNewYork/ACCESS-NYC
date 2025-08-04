<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Handlers;

interface Routing_Handler {

	/**
	 * Handle the routing callback from gravitysmtp_connector_for_sending
	 *
	 * @param string $current_connector The current connector chosen for sending.
	 * @param array  $email_data        The data for the current email.
	 *
	 * @return string The connector to use for the email.
	 */
	public function handle( $current_connector, $email_data );

}