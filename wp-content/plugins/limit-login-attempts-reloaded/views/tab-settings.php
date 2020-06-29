<?php

if( !defined( 'ABSPATH' ) ) exit();

/**
 * @var $this Limit_Login_Attempts
 */

$gdpr = $this->get_option( 'gdpr' );

$v = explode( ',', $this->get_option( 'lockout_notify' ) );
$log_checked = in_array( 'log', $v ) ? ' checked ' : '';
$email_checked = in_array( 'email', $v ) ? ' checked ' : '';

$admin_notify_email = $this->get_option( 'admin_notify_email' );
$admin_email_placeholder = (!is_multisite()) ? get_option( 'admin_email' ) : get_site_option( 'admin_email' );

$trusted_ip_origins = $this->get_option( 'trusted_ip_origins' );
$trusted_ip_origins = ( is_array( $trusted_ip_origins ) && !empty( $trusted_ip_origins ) ) ? implode( ", ", $trusted_ip_origins ) : 'REMOTE_ADDR';
?>

<h3><?php echo __( 'General Settings', 'limit-login-attempts-reloaded' ); ?></h3>
<p><?php echo __( 'These settings are independent of the workers (see below).', 'limit-login-attempts-reloaded' ); ?></p>
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
            <th scope="row"
                valign="top"><?php echo __( 'Notify on lockout', 'limit-login-attempts-reloaded' ); ?></th>
            <td>
				<?php /*
                <input type="checkbox" name="lockout_notify_log" <?php echo $log_checked; ?>
                       value="log"/> <?php echo __( 'Lockout log', 'limit-login-attempts-reloaded' ); ?><br/>
                */ ?>

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
    </table>

    <h3><?php echo __( 'Worker Settings', 'limit-login-attempts-reloaded' ); ?></h3>
    <p><?php echo __( 'The workers absorb the main load caused by brute-force attacks, analyse login attempts and block unwanted visitors. They might provide other service functions as well.', 'limit-login-attempts-reloaded' ); ?></p>

    <div id="llar-workers-accordion" class="llar-accordion">
        <h3>Local Worker</h3>
        <div>
            <table class="form-table">
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
                        valign="top"><?php echo __( 'Trusted IP Origins', 'limit-login-attempts-reloaded' ); ?></th>
                    <td>
                        <div class="field-col">
                            <input type="text" class="regular-text" style="width: 100%;max-width: 431px;" name="lla_trusted_ip_origins" value="<?php echo esc_attr( $trusted_ip_origins ); ?>">
                            <p class="description"><?php _e( 'Specify the origins you trust in order of priority, separated by commas. We strongly recommend that you <b>do not</b> use anything other than REMOTE_ADDR since other origins can be easily faked. Examples: HTTP_X_FORWARDED_FOR, HTTP_CF_CONNECTING_IP, HTTP_X_SUCURI_CLIENTIP', 'limit-login-attempts-reloaded' ); ?></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <h3>Custom Worker</h3>
        <div>
            <p>
                In the future versions of the plugin you will be able to create your own worker. This will allow you to share White/Black lists and lockout functionality across all your websites. Stay tuned.
            </p>
        </div>
    </div>

    <script>
        (function($){

            $(document).ready(function(){

                $( "#llar-workers-accordion" ).accordion();
            });

        })(jQuery);
    </script>

    <p class="submit">
        <input class="button button-primary" name="llar_update_settings" value="<?php echo __( 'Save Settings', 'limit-login-attempts-reloaded' ); ?>"
               type="submit"/>
    </p>
</form>

