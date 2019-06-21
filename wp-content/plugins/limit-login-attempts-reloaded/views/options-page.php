<?php

if( !defined( 'ABSPATH' ) )
    exit();

/**
 * @var $this Limit_Login_Attempts
 */

$gdpr = $this->get_option( 'gdpr', 0 );

$lockouts_total = $this->get_option( 'lockouts_total', 0 );
$lockouts = $this->get_option( 'login_lockouts' );
$lockouts_now = is_array( $lockouts ) ? count( $lockouts ) : 0;

$v = explode( ',', $this->get_option( 'lockout_notify' ) );
$log_checked = in_array( 'log', $v ) ? ' checked ' : '';
$email_checked = in_array( 'email', $v ) ? ' checked ' : '';

$white_list_ips = $this->get_option( 'whitelist' );
$white_list_ips = ( is_array( $white_list_ips ) && !empty( $white_list_ips ) ) ? implode( "\n", $white_list_ips ) : '';

$white_list_usernames = $this->get_option( 'whitelist_usernames' );
$white_list_usernames = ( is_array( $white_list_usernames ) && !empty( $white_list_usernames ) ) ? implode( "\n", $white_list_usernames ) : '';

$black_list_ips = $this->get_option( 'blacklist' );
$black_list_ips = ( is_array( $black_list_ips ) && !empty( $black_list_ips ) ) ? implode( "\n", $black_list_ips ) : '';

$black_list_usernames = $this->get_option( 'blacklist_usernames' );
$black_list_usernames = ( is_array( $black_list_usernames ) && !empty( $black_list_usernames ) ) ? implode( "\n", $black_list_usernames ) : '';

$admin_notify_email = $this->get_option( 'admin_notify_email' );
$admin_email_placeholder = (!is_multisite()) ? get_option( 'admin_email' ) : get_site_option( 'admin_email' );

$trusted_ip_origins = $this->get_option( 'trusted_ip_origins' );
$trusted_ip_origins = ( is_array( $trusted_ip_origins ) && !empty( $trusted_ip_origins ) ) ? implode( ", ", $trusted_ip_origins ) : 'REMOTE_ADDR';

?>
<div class="wrap limit-login-page-settings">
    <h2><?php echo __( 'Limit Login Attempts Settings', 'limit-login-attempts-reloaded' ); ?></h2>
    <h3><?php echo __( 'Statistics', 'limit-login-attempts-reloaded' ); ?></h3>
    <form action="<?php echo $this->get_options_page_uri(); ?>" method="post">
        <?php wp_nonce_field( 'limit-login-attempts-options' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row" valign="top"><?php echo __( 'Total lockouts', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <?php if( $lockouts_total > 0 ) { ?>
                        <input class="button" name="reset_total"
                               value="<?php echo __( 'Reset Counter', 'limit-login-attempts-reloaded' ); ?>"
                               type="submit"/>
                        <?php echo sprintf( _n( '%d lockout since last reset', '%d lockouts since last reset', $lockouts_total, 'limit-login-attempts-reloaded' ), $lockouts_total ); ?>
                    <?php } else {
                        echo __( 'No lockouts yet', 'limit-login-attempts-reloaded' );
                    } ?>
                </td>
            </tr>
            <?php if( $lockouts_now > 0 ) { ?>
                <tr>
                    <th scope="row"
                        valign="top"><?php echo __( 'Active lockouts', 'limit-login-attempts-reloaded' ); ?></th>
                    <td>
                        <input class="button" name="reset_current"
                               value="<?php echo __( 'Restore Lockouts', 'limit-login-attempts-reloaded' ); ?>"
                               type="submit"/>
                        <?php echo sprintf( __( '%d IP is currently blocked from trying to log in', 'limit-login-attempts-reloaded' ), $lockouts_now ); ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </form>
    <h3><?php echo __( 'Options', 'limit-login-attempts-reloaded' ); ?></h3>
    <form action="<?php echo $this->get_options_page_uri(); ?>" method="post">
        <?php wp_nonce_field( 'limit-login-attempts-options' ); ?>
        <?php if ( is_network_admin() ): ?>
		<input type="checkbox" name="allow_local_options" <?php echo $this->get_option( 'allow_local_options' ) ? 'checked' : '' ?> value="1"/> <?php esc_html_e( 'Let network sites use their own settings', 'limit-login-attempts-reloaded' ); ?>
		<p class="description"><?php esc_html_e('If disabled, the global settings will be forcibly applied to the entire network.') ?></p>
		<?php elseif ( $this->network_mode ): ?>
        <input type="checkbox" name="use_global_options" <?php echo $this->get_option('use_local_options' ) ? '' : 'checked' ?> value="1" class="use_global_options"/> <?php echo __( 'Use global settings', 'limit-login-attempts-reloaded' ); ?><br/>
        <script>
        jQuery(function($){
			var first = true;
			$('.use_global_options').change( function(){
				var form = $(this).siblings('table');
				form.stop();

				if ( this.checked )
					first ? form.hide() : form.fadeOut();
				else
					first ? form.show() : form.fadeIn();

				first = false;
			}).change();
        });
        </script>
        <?php endif ?>
        <table class="form-table">
            <tr>
                <th scope="row"
                    valign="top"><?php echo __( 'GDPR compliance', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <input type="checkbox" name="gdpr" value="1" <?php if($gdpr): ?> checked <?php endif; ?>/>
                    <?php echo __( 'this makes the plugin <a href="https://gdpr-info.eu/" target="_blank" >GDPR</a> compliant', 'limit-login-attempts-reloaded' ); ?> <br/>
                </td>
            </tr>
            <tr>
                <th scope="row" valign="top"><?php echo __( 'Lockout', 'limit-login-attempts-reloaded' ); ?></th>
                <td>

                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'allowed_retries' ) ); ?>"
                           name="allowed_retries"/> <?php echo __( 'allowed retries', 'limit-login-attempts-reloaded' ); ?>
                    <br/>
                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'lockout_duration' ) / 60 ); ?>"
                           name="lockout_duration"/> <?php echo __( 'minutes lockout', 'limit-login-attempts-reloaded' ); ?>
                    <br/>
                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'allowed_lockouts' ) ); ?>"
                           name="allowed_lockouts"/> <?php echo __( 'lockouts increase lockout time to', 'limit-login-attempts-reloaded' ); ?>
                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'long_duration' ) / 3600 ); ?>"
                           name="long_duration"/> <?php echo __( 'hours', 'limit-login-attempts-reloaded' ); ?> <br/>
                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'valid_duration' ) / 3600 ); ?>"
                           name="valid_duration"/> <?php echo __( 'hours until retries are reset', 'limit-login-attempts-reloaded' ); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"
                    valign="top"><?php echo __( 'Notify on lockout', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <input type="checkbox" name="lockout_notify_log" <?php echo $log_checked; ?>
                           value="log"/> <?php echo __( 'Lockout log', 'limit-login-attempts-reloaded' ); ?><br/>
                    <input type="checkbox" name="lockout_notify_email" <?php echo $email_checked; ?>
                           value="email"/> <?php echo __( 'Email to', 'limit-login-attempts-reloaded' ); ?>
                    <input type="email" name="admin_notify_email"
                           value="<?php echo esc_attr( $admin_notify_email ) ?>"
                           placeholder="<?php echo esc_attr( $admin_email_placeholder ); ?>"/> <?php echo __( 'after', 'limit-login-attempts-reloaded' ); ?>
                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'notify_email_after' ) ); ?>"
                           name="email_after"/> <?php echo __( 'lockouts', 'limit-login-attempts-reloaded' ); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"
                    valign="top"><?php echo __( 'Whitelist', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <div class="field-col">
                        <p class="description"><?php _e( 'One IP or IP range (1.2.3.4-5.6.7.8) per line', 'limit-login-attempts-reloaded' ); ?></p>
                        <textarea name="lla_whitelist_ips" rows="10" cols="50"><?php echo esc_textarea( $white_list_ips ); ?></textarea>
                    </div>
                    <div class="field-col">
                        <p class="description"><?php _e( 'One Username per line', 'limit-login-attempts-reloaded' ); ?></p>
                        <textarea name="lla_whitelist_usernames" rows="10" cols="50"><?php echo esc_textarea( $white_list_usernames ); ?></textarea>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"
                    valign="top"><?php echo __( 'Blacklist', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <div class="field-col">
                        <p class="description"><?php _e( 'One IP or IP range (1.2.3.4-5.6.7.8) per line', 'limit-login-attempts-reloaded' ); ?></p>
                        <textarea name="lla_blacklist_ips" rows="10" cols="50"><?php echo esc_textarea( $black_list_ips ); ?></textarea>
                    </div>
                    <div class="field-col">
                        <p class="description"><?php _e( 'One Username per line', 'limit-login-attempts-reloaded' ); ?></p>
                        <textarea name="lla_blacklist_usernames" rows="10" cols="50"><?php echo esc_textarea( $black_list_usernames ); ?></textarea>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"
                    valign="top"><?php echo __( 'Trusted IP Origins', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <div class="field-col">
                        <input type="text" class="regular-text" style="width: 100%;max-width: 431px;" name="lla_trusted_ip_origins" value="<?php echo esc_attr( $trusted_ip_origins ); ?>">
                        <p class="description"><?php _e( 'Specify the origins you trust in order of priority, separated by commas. We strongly recommend that you <b>do not</b> use anything other than REMOTE_ADDR since other origins can be easily faked. Examples: HTTP_X_FORWARDED_FOR, HTTP_CF_CONNECTING_IP, HTTP_X_SUCURI_CLIENTIP', 'limit-login-attempts-reloaded' ); ?></p>
                    </div>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input class="button button-primary" name="update_options" value="<?php echo __( 'Save Options', 'limit-login-attempts-reloaded' ); ?>"
                   type="submit"/>
        </p>
    </form>
    <?php
    $log = $this->get_option( 'logged' );
    $log = LLA_Helpers::sorted_log_by_date( $log );

    $lockouts = (array)$this->get_option('lockouts');

    if( is_array( $log ) && ! empty( $log ) ) { ?>
        <h3><?php echo __( 'Lockout log', 'limit-login-attempts-reloaded' ); ?></h3>
        <form action="<?php echo $this->get_options_page_uri(); ?>" method="post">
            <?php wp_nonce_field( 'limit-login-attempts-options' ); ?>
            <input type="hidden" value="true" name="clear_log"/>
            <p class="submit">
                <input class="button" name="submit" value="<?php echo __( 'Clear Log', 'limit-login-attempts-reloaded' ); ?>"
                       type="submit"/>
            </p>
        </form>

        <div class="limit-login-log">
            <table class="form-table">
                <tr>
                    <th scope="col"><?php _e( "Date", 'limit-login-attempts-reloaded' ); ?></th>
                    <th scope="col"><?php echo _x( "IP", "Internet address", 'limit-login-attempts-reloaded' ); ?></th>
                    <th scope="col"><?php _e( 'Tried to log in as', 'limit-login-attempts-reloaded' ); ?></th>
                    <th scope="col"><?php _e( 'Gateway', 'limit-login-attempts-reloaded' ); ?></th>
                    <th>
                </tr>

                <?php foreach ( $log as $date => $user_info ) : ?>
                    <tr>
                        <td class="limit-login-date"><?php echo date_i18n( 'F d, Y H:i', $date ); ?></td>
                        <td class="limit-login-ip">
                                <?php echo esc_html( $user_info['ip'] ); ?>
                        </td>
                        <td class="limit-login-max"><?php echo esc_html( $user_info['username'] ) . ' (' . esc_html( $user_info['counter'] ) .' lockouts)'; ?></td>
                        <td class="limit-login-gateway"><?php echo esc_html( $user_info['gateway'] ); ?></td>
                        <td>
                            <?php if ( !empty( $lockouts[ $user_info['ip'] ] ) && $lockouts[ $user_info['ip'] ] > time() ) : ?>
                            <a href="#" class="button limit-login-unlock" data-ip="<?=esc_attr($user_info['ip'])?>" data-username="<?=esc_attr($user_info['username'])?>">Unlock</a>
                            <?php elseif ( $user_info['unlocked'] ): ?>
                            Unlocked
                            <?php endif ?>
                    </tr>
                <?php endforeach; ?>

            </table>
        </div>
        <script>jQuery( function($) {
          $('.limit-login-log .limit-login-unlock').click( function()
          {
              var btn = $(this);

              if ( btn.hasClass('disabled') )
                return false;
              btn.addClass( 'disabled' );

              $.post( ajaxurl, {
                action: 'limit-login-unlock',
                sec: '<?=wp_create_nonce('limit-login-unlock') ?>',
                ip: btn.data('ip'),
                username: btn.data('username')
              } )
              .done( function(data) {
                if ( data === true )
                  btn.fadeOut( function(){ $(this).parent().text('Unlocked') });
                else
                  fail();
              }).fail( fail );

              function fail() {
                alert('Connection error');
                btn.removeClass('disabled');
              }

              return false;
            } );
          } )</script>
        <?php
    } /* if showing $log */
    ?>

</div>