<?php

class Referrer_Match extends Red_Match {
	public $referrer;
	public $regex;

	function name() {
		return __( 'URL and referrer', 'redirection' );
	}

	function show() {
		$codes = array(
			301 => get_status_header_desc( 301 ),
			302 => get_status_header_desc( 302 ),
			307 => get_status_header_desc( 307 ),
		 );

		?>
		<tr>
			<th width="100"><?php _e( 'Referrer', 'redirection' ); ?>:</th>
			<td valign="top">
				<input style="width: 85%" type="text" name="referrer" value="<?php echo esc_attr( $this->referrer ); ?>"/>
				<label><?php _e( 'Regex', 'redirection' ); ?>: <input type="checkbox" name="regex" <?php if ( $this->regex === true ) echo ' checked="checked"' ?>/></label>
			</td>
		</tr>
		<tr>
			<th><?php _e( 'HTTP Code', 'redirection' ); ?>:</th>
			<td>
				<select name="action_code">
					<?php foreach ( $codes as $key => $code ) : ?>
						<option value="<?php echo $key ?>"<?php if ( $key === intval( $this->action_code ) ) echo ' selected="selected"' ?>><?php printf( '%s - %s', $key, $code ) ?></option>
					<?php endforeach?>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php _e( 'HTTP Code', 'redirection' ); ?>:</th>
			<td>
				<p style="padding: 0.5em"><?php _e( 'The visitor will be redirected from the source URL if the referrer matches.  You can specify a <em>matched</em> target URL as the address to send visitors if they do match, and <em>not matched</em> if they don\'t match.  Leaving a URL blank means that the visitor is not redirected.', 'redirection' ); ?></p>
			</td>
		</tr>
		<tr>
			<th width="100" valign="top">
				<?php if ( strlen( $this->url_from ) > 0 ) : ?>
				<a target="_blank" href="<?php echo esc_url( $this->url_from ) ?>"><?php _e( 'Matched', 'redirection' ); ?>:</a>
				<?php else : ?>
				<?php _e( 'Matched', 'redirection' ); ?>:
				<?php endif; ?>
			</th>
			<td valign="top"><input style="width: 95%" type="text" name="url_from" value="<?php echo esc_attr( $this->url_from ); ?>" id="new"/></td>
		</tr>
		<tr>
			<th width="100" valign="top">
				<?php if ( strlen( $this->url_notfrom ) > 0 ) : ?>
				<a target="_blank" href="<?php echo $this->url_notfrom ?>"><?php _e( 'Not matched', 'redirection' ); ?>:</a>
				<?php else : ?>
				<?php _e( 'Not matched', 'redirection' ); ?>:
				<?php endif; ?>
			</th>
			<td valign="top">
				<input style="width: 95%" type="text" name="url_notfrom" value="<?php echo esc_attr( $this->url_notfrom ); ?>" id="new"/><br/>
			</td>
		</tr>
		<?php
	}

	function save( $details ) {
		if ( isset( $details['target'] ) )
			$details['url_from'] = $this->sanitize_url( $details['target'] );

		return array(
			'url_from'    => $this->sanitize_url( $details['url_from'] ),
			'url_notfrom' => isset( $details['url_notfrom'] ) ? $this->sanitize_url( $details['url_notfrom'] ) : false,
			'regex'       => isset( $details['regex'] ) ? true : false,
			'referrer'    => isset( $details['referrer'] ) ? $this->sanitize_referrer( $details['referrer'] ) : false,
		);
	}

	public function sanitize_referrer( $agent ) {
		return $this->sanitize_url( $agent );
	}

	function initialize( $url ) {
		$this->url = array( $url, '' );
	}

	function wants_it() {
		// Match referrer
		return true;
	}

	function get_target( $url, $matched_url, $regex ) {
		$target = false;

		// Check if referrer matches
		if ( ( $this->regex === false && $_SERVER['HTTP_REFERER'] === $this->referrer ) || ( $this->regex === true && preg_match( '@'.str_replace( '@', '\\@', $this->referrer ).'@', $_SERVER['HTTP_REFERER'], $matches ) ) ) {
			$target = $this->url_from;

			if ( $regex )
				$target = preg_replace( '@'.str_replace( '@', '\\@', $matched_url ).'@', $target, $url );
		}
		elseif ( $this->url_notfrom !== '' )
			$target = $this->url_notfrom;
		return $target;
	}

	function match_name() {
		return sprintf( 'referrer - <code>%s</code>', $this->referrer );
	}
}
