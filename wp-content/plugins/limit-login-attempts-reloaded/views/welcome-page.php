<?php
if (!defined('ABSPATH')) exit();
?>

<div id="llar-welcome-page">
    <div class="llar-welcome-page-container">
        <div class="llar-welcome-page-section">
            <div class="llar-logo">
                <a href="https://www.limitloginattempts.com/" target="_blank">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/logo.png"
                         alt="<?php esc_attr_e('Limit Login Attempts Reloaded', 'limit-login-attempts-reloaded'); ?>">
                </a>
            </div>
            <h2><?php esc_html_e('Welcome to Limit Login Attempts Reloaded', 'limit-login-attempts-reloaded'); ?></h2>
            <p><?php esc_html_e('Thank you for choosing Limit Login Attempts Reloaded - A simple, yet powerful bot-blocking plugin that keeps your login page safe. ', 'limit-login-attempts-reloaded'); ?></p>

            <h2><?php esc_html_e('With This Plugin, You Can…', 'limit-login-attempts-reloaded'); ?></h2>
            <ul class="llar-welcome-list">
                <li><span class="dashicons dashicons-star-filled"></span><?php _e('View who’s trying to access your site', 'limit-login-attempts-reloaded'); ?></li>
                <li><span class="dashicons dashicons-star-filled"></span><?php _e('Protect from future attacks by allowing or denying IPs', 'limit-login-attempts-reloaded'); ?></li>
                <li><span class="dashicons dashicons-star-filled"></span><?php _e('Optimize site performance by redirecting malicious logins', 'limit-login-attempts-reloaded'); ?></li>
                <li><span class="dashicons dashicons-star-filled"></span><?php _e('Receive email alerts when your site is under attack', 'limit-login-attempts-reloaded'); ?></li>
                <li><span class="dashicons dashicons-star-filled"></span><?php _e('Synchronize lockout data across a network of sites', 'limit-login-attempts-reloaded'); ?></li>
            </ul>
            <h2><?php esc_html_e('And Much More!', 'limit-login-attempts-reloaded'); ?></h2>
        </div>
        <div class="llar-welcome-page-video-wrap">
            <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/llar-video-1.jpg" class="play-video" data-id="IsotthPWCPA">
        </div>
        <div class="llar-welcome-page-section">
            <p><?php esc_html_e('Although we recommend using the default settings, you can get a quick video tutorial if you wanted to learn more about the plugin and what it does.', 'limit-login-attempts-reloaded'); ?></p>
        </div>
        <div class="llar-welcome-page-video-wrap">
            <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/llar-video-2.jpg" class="play-video" data-id="xXAcLYC_Sis">
        </div>
        <div class="llar-welcome-page-section">
            <p><?php echo sprintf(__('If you have more questions, please check out the <a href="%s" target="_blank">help section</a> on our website.', 'limit-login-attempts-reloaded'), 'https://www.limitloginattempts.com/resources/?from=plugin-welcome'); ?></p>
        </div>
    </div>
    <div class="llar-welcome-page-container llar-premium">
        <div class="llar-welcome-page-section">
            <h2><?php _e('2,000,000+ active installations on <br>WordPress worldwide!', 'limit-login-attempts-reloaded'); ?></h2>
            <h3><?php echo sprintf(
                    __('Looking for advanced protection? <a href="%s" target="_blank">Try our premium service</a> for only $4.99/month!', 'limit-login-attempts-reloaded'),
                    'https://www.limitloginattempts.com/info.php?from=plugin-welcome'
                ); ?></h3>
        </div>
        <div class="llar-welcome-page-features">
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon1.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Performance Optimizer', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('We absorb up to 100k login attempts (per month) to keep your site at optimal performance', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon2.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Lockout/IP Throttling', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('Longer lockout intervals each time a hacker/bot tries to login unsuccessfully', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon11.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Premium Support Forum', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('Get your technical questions answered by our experts', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon4.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Intelligent IP Blocking/Unblocking', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('We make sure the legitimate IP’s are allowed automatically', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon5.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('CSV Download of IP Data', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('CSV Download of all IP data', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon6.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Synchronized Lockouts', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('Lockouts can be shared between multiple domains', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon7.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Synchronized Safelist/Blacklist', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('Safelist/Blacklists can be shared between multiple domains', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
            <div class="llar-feature-item">
                <div class="llar-feature-image">
                    <img src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/features/icon3.png">
                </div>
                <div class="llar-feature-info">
                    <div class="llar-feature-title"><?php _e('Auto Backup Of All Data', 'limit-login-attempts-reloaded'); ?></div>
                    <div class="llar-feature-desc"><?php _e('Regular cloud backups of all data', 'limit-login-attempts-reloaded'); ?></div>
                </div>
            </div>
        </div>
        <div class="llar-welcome-page-section">
            <h2><?php _e('We highly recommend upgrading if you are a…', 'limit-login-attempts-reloaded'); ?></h2>
            <div class="llar-why-recommend">
                <ul>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('Digital Agency', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('Site admin w/ multiple domains', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('E-commerce website', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('A forum', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('A website with a lot of traffic', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('A website that holds sensitive data', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('A website with hundreds or thousands of registered users', 'limit-login-attempts-reloaded'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span><?php _e('A website that is constantly being attacked', 'limit-login-attempts-reloaded'); ?></li>
                </ul>
            </div>
            <div class="llar-upgrade-btn-wrap">
                <a href="https://www.limitloginattempts.com/info.php?from=plugin-welcome" target="_blank"><?php _e( 'Upgrade Now', 'limit-login-attempts-reloaded' ); ?></a>
            </div>
            <div class="llar-upgrade-questions"><?php _e( 'Questions? Email us at <a href="mailto:sales@limitloginattempts.com">sales@limitloginattempts.com</a>', 'limit-login-attempts-reloaded' ); ?></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    (function($){

		$(document).on( 'click', '#llar-welcome-page .play-video', function(e) {
			e.preventDefault();

			var video_id = $(this).data('id'),
                video = '<div class="video-container"><iframe width="1280" height="720" src="https://www.youtube.com/embed/'+video_id+'?rel=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe></div>';

			$.dialog({
				title: false,
				content: video,
				closeIcon: true,
				boxWidth: '70%',
				useBootstrap: false,
				backgroundDismiss: true
			});
		});

    })(jQuery);
</script>