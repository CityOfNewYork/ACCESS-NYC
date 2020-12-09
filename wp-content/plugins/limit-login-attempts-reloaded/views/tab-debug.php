<?php

if( !defined( 'ABSPATH' ) ) exit();

/**
 * @var $this Limit_Login_Attempts
 */

$debug_info = '';

$ips = $server = array();
foreach ($_SERVER as $key => $value) {

	if(in_array($key, ['SERVER_ADDR'])) continue;

	if( $this->is_ip_valid( $value ) ) {

	    if(!in_array($value, $ips)) {

			$ips[] = $value;
		}

		if(in_array($value, ['127.0.0.1', '0.0.0.0']))
			$server[$key] = $value;
		else
			$server[$key] = 'IP'.array_search($value, $ips);
	}
}

foreach ($server as $server_key => $ip ) {
	$debug_info .= $server_key . ' = ' . $ip . "\n";
}
?>

<table class="form-table">
	<tr>
		<th scope="row" valign="top"><?php echo __( 'Debug info', 'limit-login-attempts-reloaded' ); ?></th>
		<td>
			<textarea cols="70" rows="10" onclick="this.select()" readonly><?php echo esc_textarea($debug_info); ?></textarea>
			<p class="description"><?php _e( 'Copy the contents of the window and provide to support.', 'limit-login-attempts-reloaded' ); ?></p>
		</td>
	</tr>
</table>
