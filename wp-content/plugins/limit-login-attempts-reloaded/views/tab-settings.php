<?php

if( !defined( 'ABSPATH' ) ) exit();

/**
 * @var $this Limit_Login_Attempts
 */

$gdpr = $this->get_option( 'gdpr' );
$gdpr_message = $this->get_option( 'gdpr_message' );

$v = explode( ',', $this->get_option( 'lockout_notify' ) );
$email_checked = in_array( 'email', $v ) ? ' checked ' : '';

$show_top_level_menu_item = $this->get_option( 'show_top_level_menu_item' );

$admin_notify_email = $this->get_option( 'admin_notify_email' );
$admin_email_placeholder = (!is_multisite()) ? get_option( 'admin_email' ) : get_site_option( 'admin_email' );

$trusted_ip_origins = $this->get_option( 'trusted_ip_origins' );
$trusted_ip_origins = ( is_array( $trusted_ip_origins ) && !empty( $trusted_ip_origins ) ) ? implode( ", ", $trusted_ip_origins ) : 'REMOTE_ADDR';

$active_app = $this->get_option( 'active_app' );
$app_setup_code = $this->get_option( 'app_setup_code' );
$active_app_config = $this->get_custom_app_config();

?>
<?php if( isset( $_GET['activated'] ) ) : ?>
<div class="llar-app-notice success">
    <p><?php echo $active_app_config['messages']['setup_success']; ?></p>
</div>
<?php endif; ?>

<h3><?php echo __( 'General Settings', 'limit-login-attempts-reloaded' ); ?></h3>
<p><?php echo __( 'These settings are independent of the apps (see below).', 'limit-login-attempts-reloaded' ); ?></p>
<form action="<?php echo $this->get_options_page_uri('settings'); ?>" method="post">

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
				<?php echo __( 'this makes the plugin <a href="https://gdpr-info.eu/" target="_blank">GDPR</a> compliant by showing a message on the login page. <a href="https://www.limitloginattempts.com/gdpr-qa/?from=plugin-settings-gdpr" target="_blank">Read more</a>', 'limit-login-attempts-reloaded' ); ?> <br/>
            </td>
        </tr>
        <tr>
            <th scope="row"
                valign="top"><?php echo __( 'GDPR message', 'limit-login-attempts-reloaded' ); ?></th>
            <td>
                <textarea name="gdpr_message" cols="60"><?php echo esc_textarea( stripslashes( $gdpr_message ) ); ?></textarea>
                <p class="description"><?php echo __( 'You can use a shortcode here to insert links, for example, a link to your Privacy Policy page. <br>The shortcode is: [llar-link url="https://example.com" text="Privacy Policy"]', 'limit-login-attempts-reloaded' ); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"
                valign="top"><?php echo __( 'Notify on lockout', 'limit-login-attempts-reloaded' ); ?></th>
            <td>
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
                valign="top"><?php echo __( 'Show top-level menu item', 'limit-login-attempts-reloaded' ); ?></th>
            <td>
                <input type="checkbox" name="show_top_level_menu_item" <?php checked( $show_top_level_menu_item ); ?>> <?php _e( '(Reload the page to see the changes)', 'limit-login-attempts-reloaded' ) ?>
            </td>
        </tr>
        <tr>
            <th scope="row"
                valign="top"><?php echo __( 'Active App', 'limit-login-attempts-reloaded' ); ?></th>
            <td>
                <select name="active_app" id="">
                    <option value="local" <?php selected( $active_app, 'local' ); ?>><?php echo __( 'Local', 'limit-login-attempts-reloaded' ); ?></option>
                    <?php if( $active_app_config ) : ?>
                    <option value="custom" <?php selected( $active_app, 'custom' ); ?>><?php echo esc_html( $active_app_config['name'] ); ?></option>
                    <?php endif; ?>
                </select>
                <?php if( $active_app === 'local' ) : ?>
                <span class="llar-protect-notice"><?php _e( 'Get advanced protection by <a href="#" class="llar-upgrade-to-cloud">upgrading to our Cloud App.</a>', 'limit-login-attempts-reloaded' ); ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <h3><?php echo __( 'App Settings', 'limit-login-attempts-reloaded' ); ?></h3>
    <p><?php echo __( 'The app absorbs the main load caused by brute-force attacks, analyzes login attempts, and blocks unwanted visitors. It provides other service functions as well.', 'limit-login-attempts-reloaded' ); ?></p>

    <div id="llar-apps-accordion" class="llar-accordion">
        <h3><?php echo __( 'Local App', 'limit-login-attempts-reloaded' ); ?></h3>
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

        <h3><?php echo ($active_app_config) ? $active_app_config['name'] : __('Custom App', 'limit-login-attempts-reloaded' ); ?></h3>
        <div class="custom-app-tab">

			<?php if( $active_app === 'custom' ) : ?>
                <a class="dashicons dashicons-admin-generic llar-show-app-fields" href="#"></a>
			<?php endif; ?>

            <table class="form-table">

                <tr class="llar-app-field <?php echo ( $active_app === 'local' || !$active_app_config ) ? 'active' : ''; ?>">
                    <th scope="row"
                        valign="top"><?php echo __( 'Setup Code', 'limit-login-attempts-reloaded' ); ?></th>
                    <td>
                        <input type="text" class="regular-text" id="limit-login-app-setup-code" value="<?php echo ( !empty( $app_setup_code ) ) ? esc_attr( $app_setup_code ) : ''; ?>">
                        <button class="button" id="limit-login-app-setup"><?php echo __( 'Submit', 'limit-login-attempts-reloaded' ); ?></button>
                        <span class="spinner llar-app-ajax-spinner"></span><br>
                        <span class="llar-app-ajax-msg"></span>

						<?php if( $active_app === 'local' ) : ?>
                        <p class="description"><?php echo sprintf(
                                __( 'Use the <a href="%s" target="_blank">premium app</a> that we offer or follow the instructions on <a href="%s" target="_blank">how to</a> create your own one.', 'limit-login-attempts-reloaded' ),
                                'https://www.limitloginattempts.com/info.php?from=plugin-settings',
                                'https://www.limitloginattempts.com/app/?from=plugin-settings' );
                        ?></p>
                        <div class="llar-why-use-premium-text">
                            <div class="title"><?php _e( 'Why Use Our Premium Cloud App?', 'limit-login-attempts-reloaded' ); ?></div>
                            <ul>
                                <li><span class="dashicons dashicons-yes"></span><?php _e( 'Absorb site load caused by attacks', 'limit-login-attempts-reloaded' ); ?></li>
                                <li><span class="dashicons dashicons-yes"></span><?php _e( 'Use intelligent IP blocking/unblocking technology', 'limit-login-attempts-reloaded' ); ?></li>
                                <li><span class="dashicons dashicons-yes"></span><?php _e( 'Sync the allow/deny/pass lists between multiple domains', 'limit-login-attempts-reloaded' ); ?></li>
                                <li><span class="dashicons dashicons-yes"></span><?php _e( 'Get premium support', 'limit-login-attempts-reloaded' ); ?></li>
                                <li><span class="dashicons dashicons-yes"></span><?php _e( 'Run auto backups of access control lists, lockouts and logs', 'limit-login-attempts-reloaded' ); ?></li>
                                <li><span class="dashicons dashicons-yes"></span><?php _e( 'Only pay $7.99/m per domain - cancel any time', 'limit-login-attempts-reloaded' ); ?></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
				<?php if( $active_app === 'custom' && $active_app_config ) : ?>
                <tr class="llar-app-field">
                    <th scope="row"
                        valign="top"><?php echo __( 'Configuration', 'limit-login-attempts-reloaded' ); ?></th>
                    <td>
                        <div class="field-col">
                            <textarea id="limit-login-app-config" readonly="readonly" name="limit-login-app-config" cols="60" rows="5"><?php echo esc_textarea( json_encode( $active_app_config ) ); ?></textarea><br>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if( $active_app === 'custom' && !empty( $active_app_config['settings'] ) ) : ?>
                    <?php foreach( $active_app_config['settings'] as $setting_name => $setting_params ) : ?>
                        <tr>
                            <th scope="row" valign="top"><?php echo $setting_params['label']; ?></th>
                            <td>
                                <div class="field-col">
                                    <?php if( !empty( $setting_params['options'] ) ) : ?>
                                        <select name="llar_app_settings[<?php echo $setting_name; ?>]">
                                            <?php foreach ( $setting_params['options'] as $option ) : ?>
                                                <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $option, $setting_params['value'] ); ?>><?php echo esc_html( $option ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <input type="text" class="regular-text" name="llar_app_settings[<?php echo esc_attr( $setting_name ); ?>]" value="<?php echo esc_attr( $setting_params['value'] ); ?>">
                                    <?php endif; ?>

                                    <p class="description"><?php echo esc_html( $setting_params['description'] ); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        (function($){

            $(document).ready(function(){

                $( "#llar-apps-accordion" ).accordion({
                    heightStyle: "content",
                    active: <?php echo ( $active_app === 'local' ) ? 0 : 1; ?>
                });

                var $app_ajax_spinner = $('.llar-app-ajax-spinner'),
                    $app_ajax_msg = $('.llar-app-ajax-msg'),
                    $app_config_field = $('#limit-login-app-config');

                if($app_config_field.val()) {
                    var pretty = JSON.stringify(JSON.parse($app_config_field.val()), undefined, 2);
                    $app_config_field.val(pretty);
                }

                $('#limit-login-app-setup').on('click', function(e) {
                    e.preventDefault();

                    $app_ajax_msg.text('').removeClass('success error');
                    $app_ajax_spinner.css('visibility', 'visible');

                    var setup_code = $('#limit-login-app-setup-code').val();

                    $.post(ajaxurl, {
                        action: 'app_setup',
                        code: setup_code,
                        sec: '<?php echo esc_js( wp_create_nonce( "llar-action" ) ); ?>'
                    }, function(response){

                        if(!response.success) {

                            $app_ajax_msg.addClass('error');
                        } else {

                            $app_ajax_msg.addClass('success');

                            setTimeout(function(){

                                window.location = window.location + '&activated';

                            }, 1000);
                        }

                        if(!response.success && response.data.msg) {

                            $app_ajax_msg.text(response.data.msg);
                        }

                        $app_ajax_spinner.css('visibility', 'hidden');

                        setTimeout(function(){

                            $app_ajax_msg.text('').removeClass('success error');

                        }, 5000);
                    });

                });

                $('.llar-show-app-fields').on('click', function(e){
                    e.preventDefault();

                    $('.llar-app-field').toggleClass('active');

                });

                $('.llar-upgrade-to-cloud').on('click', function(e){
                	e.preventDefault();

					$("#ui-id-3").click();

                    $([document.documentElement, document.body]).animate({
                        scrollTop: $("#llar-apps-accordion").offset().top
                    }, 500);
                });

            });

        })(jQuery);
    </script>

    <p class="submit">
        <input class="button button-primary" name="llar_update_settings" value="<?php echo __( 'Save Settings', 'limit-login-attempts-reloaded' ); ?>"
               type="submit"/>
    </p>
</form>

