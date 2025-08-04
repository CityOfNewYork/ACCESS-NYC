<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Connectors;

interface Alert_Connector {

	public function send( $send_args );

	public function make_request( $url, $request_args );

}