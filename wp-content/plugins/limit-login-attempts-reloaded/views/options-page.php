<?php

if( !defined( 'ABSPATH' ) )
    exit();

/**
 * @var $this Limit_Login_Attempts
 */

if( !current_user_can( 'manage_options' ) ) {
    wp_die( 'Sorry, but you do not have permissions to change settings.' );
}

/* Make sure post was from this page */
if( !empty( $_POST ) ) {
    check_admin_referer( 'limit-login-attempts-options' );
}

/* Should we clear log? */
if( isset( $_POST[ 'clear_log' ] ) ) {
    delete_option( 'limit_login_logged' );
    $this->show_error( __( 'Cleared IP log', 'limit-login-attempts-reloaded' ) );
}

/* Should we reset counter? */
if( isset( $_POST[ 'reset_total' ] ) ) {
    update_option( 'limit_login_lockouts_total', 0 );
    $this->show_error( __( 'Reset lockout count', 'limit-login-attempts-reloaded' ) );
}

/* Should we restore current lockouts? */
if( isset( $_POST[ 'reset_current' ] ) ) {
    update_option( 'limit_login_lockouts', array() );
    $this->show_error( __( 'Cleared current lockouts', 'limit-login-attempts-reloaded' ) );
}

/* Should we update options? */
if( isset( $_POST[ 'update_options' ] ) ) {

    $this->_options[ 'allowed_retries' ]    = $_POST[ 'allowed_retries' ];
    $this->_options[ 'lockout_duration' ]   = $_POST[ 'lockout_duration' ] * 60;
    $this->_options[ 'valid_duration' ]     = $_POST[ 'valid_duration' ] * 3600;
    $this->_options[ 'allowed_lockouts' ]   = $_POST[ 'allowed_lockouts' ];
    $this->_options[ 'long_duration' ]      = $_POST[ 'long_duration' ] * 3600;
    $this->_options[ 'notify_email_after' ] = $_POST[ 'email_after' ];

    $white_list = ( !empty( $_POST['lla_whitelist'] ) ) ? explode("\n", str_replace("\r", "", $_POST['lla_whitelist'] ) ) : array();

    if( !empty( $white_list ) ) {
        foreach( $white_list as $key => $ip ) {
            if( '' == $ip ) {
                unset( $white_list[ $key ] );
            }
        }
    }

    $this->_options['whitelist'] = $white_list;

    $notify_methods = array();
    if( isset( $_POST[ 'lockout_notify_log' ] ) ) {
        $notify_methods[] = 'log';
    }
    if( isset( $_POST[ 'lockout_notify_email' ] ) ) {
        $notify_methods[] = 'email';
    }
    $this->_options[ 'lockout_notify' ] = implode( ',', $notify_methods );

    $this->sanitize_variables();
    $this->update_options();

    $this->show_error( __( 'Options changed', 'limit-login-attempts-reloaded' ) );
}

$lockouts_total = get_option( 'limit_login_lockouts_total', 0 );
$lockouts = get_option( 'limit_login_lockouts' );
$lockouts_now = is_array( $lockouts ) ? count( $lockouts ) : 0;

$v = explode( ',', $this->get_option( 'lockout_notify' ) );
$log_checked = in_array( 'log', $v ) ? ' checked ' : '';
$email_checked = in_array( 'email', $v ) ? ' checked ' : '';

$white_list = $this->get_option( 'whitelist' );
$white_list = ( is_array( $white_list ) && !empty( $white_list ) ) ? implode( "\n", $white_list ) : '';
?>
<div class="wrap">
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
                    valign="top"><?php echo __( 'Notify on lockout', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <input type="checkbox" name="lockout_notify_log" <?php echo $log_checked; ?>
                           value="log"/> <?php echo __( 'Log IP', 'limit-login-attempts-reloaded' ); ?><br/>
                    <input type="checkbox" name="lockout_notify_email" <?php echo $email_checked; ?>
                           value="email"/> <?php echo __( 'Email to admin after', 'limit-login-attempts-reloaded' ); ?>
                    <input type="text" size="3" maxlength="4"
                           value="<?php echo( $this->get_option( 'notify_email_after' ) ); ?>"
                           name="email_after"/> <?php echo __( 'lockouts', 'limit-login-attempts-reloaded' ); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"
                    valign="top"><?php echo __( 'Whitelist (IP)', 'limit-login-attempts-reloaded' ); ?></th>
                <td>
                    <p class="description"><?php _e( 'One IP per line.', 'limit-login-attempts-reloaded' ); ?></p>
                    <textarea name="lla_whitelist" rows="10" cols="50"><?php echo $white_list; ?></textarea>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input class="button button-primary" name="update_options" value="<?php echo __( 'Change Options', 'limit-login-attempts-reloaded' ); ?>"
                   type="submit"/>
        </p>
    </form>
    <?php
    $log = get_option( 'limit_login_logged' );
//echo '<pre>';print_r($log);exit();
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
                </tr>

                <?php foreach ( $log as $ip => $users ) : ?>
                    <?php foreach ( $users as $user_name => $info ) : ?>
                        <tr>
                            <?php
                            // For new plugin version
                            if( is_array( $info ) ) : ?>
                            <td class="limit-login-date"><?php echo date_i18n( 'F d, Y H:i', $info['date'] ); ?></td>
                            <td class="limit-login-ip"><?php echo $ip; ?></td>
                            <td class="limit-login-max"><?php echo $user_name . ' (' . $info['counter'] .' lockouts)'; ?></td>
                            <td class="limit-login-gateway"><?php echo ( isset( $info['gateway'] ) && !empty( $info['gateway'] ) ) ? $info['gateway'] : '-'; ?></td>
                            <?php else : // For old plugin version ?>
                            <td class="limit-login-date"></td>
                            <td class="limit-login-ip"><?php echo $ip; ?></td>
                            <td class="limit-login-max"><?php echo $user_name . ' (' . $info .' lockouts)'; ?></td>
                            <td class="limit-login-gateway">-</td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>

            </table>
        </div>
        <?php
    } /* if showing $log */
    ?>

</div>