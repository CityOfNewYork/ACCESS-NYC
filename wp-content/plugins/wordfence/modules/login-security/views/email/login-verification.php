<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var string $ip The requesting IP. Required.
 * @var string $siteName The site name. Required.
 * @var string $verificationURL The verification URL. Required.
 * @var bool $canEnable2FA Whether or not the user this is being sent to can enable 2FA. Optional
 */
?>
<strong><?php echo wp_kses(sprintf(/* translators: site name */ __('Please verify a login attempt for your account on: %s', 'wordfence'), $siteName), array('strong'=>array())); ?></strong>
<br><br>
<?php echo '<strong>' . esc_html__('Request Time:', 'wordfence') . '</strong> ' . esc_html(\WordfenceLS\Controller_Time::format_local_time('F j, Y h:i:s A')); ?><br>
<?php echo '<strong>' . esc_html__('IP:', 'wordfence') . '</strong> ' . esc_html($ip); ?>
<br><br>
<?php echo wp_kses(__('The request was flagged as suspicious, and we need verification that you attempted to log in to allow it to proceed. This verification link <b>will be valid for 15 minutes</b> from the time it was sent. If you did not attempt this login, please change your password immediately.', 'wordfence'), array('b'=>array())); ?>
<br><br>
<?php echo wp_kses(sprintf(/* translators: Verification URL */ __('If you were attempting to log in to this site, <a href="%s"><strong>Verify and Log In</strong></a>', 'wordfence'), esc_url($verificationURL)), array('a' => array('href' => array()), 'strong' => array())); ?>