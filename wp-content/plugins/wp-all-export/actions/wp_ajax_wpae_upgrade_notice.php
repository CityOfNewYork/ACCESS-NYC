<?php

function pmxe_wp_ajax_wpae_upgrade_notice()
{
    if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
        exit(json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))));
    }

    if (!current_user_can(PMXE_Plugin::$capabilities)) {
        exit(json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))));
    }

	?>
	<style type="text/css">
		.easing-spinner {
            width: 30px;
            height: 30px;
            position: relative;
            display: inline-block;

            margin-top: 7px;
            margin-left: -25px;

            float: left;
        }

        .double-bounce1, .double-bounce2 {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #fff;
            opacity: 0.6;
            position: absolute;
            top: 0;
            left: 0;

            -webkit-animation: sk-bounce 2.0s infinite ease-in-out;
            animation: sk-bounce 2.0s infinite ease-in-out;
        }

        .double-bounce2 {
            -webkit-animation-delay: -1.0s;
            animation-delay: -1.0s;
        }

        .wpai-save-button svg {
            margin-top: 7px;
            margin-left: -215px;
            display: inline-block;
            position: relative;
        }

        @-webkit-keyframes sk-bounce {
            0%, 100% {
                -webkit-transform: scale(0.0)
            }
            50% {
                -webkit-transform: scale(1.0)
            }
        }

        @keyframes sk-bounce {
            0%, 100% {
                transform: scale(0.0);
                -webkit-transform: scale(0.0);
            }
            50% {
                transform: scale(1.0);
                -webkit-transform: scale(1.0);
            }
        }
	</style>
	<div id="post-preview" class="wpallexport-preview wpallexport-upgrade-notice">
        <a class="custom-close" href="#"></a>
        <div class="upgrade">
    		<h1><?php esc_html_e('Exporting Users is a Pro Feature'); ?></h1>
    		<h2><?php esc_html_e('Purchase a Pro package to export users, along with<br>many other powerful features.'); ?></h2>

    		<div class="features">
    			<div class="column list">
    				<span><?php esc_html_e('Export all user data'); ?></span>
    				<span><?php esc_html_e('Drag & drop interface'); ?></span>
    				<span><?php esc_html_e('Create any CSV or XML'); ?></span>
    				<span><?php esc_html_e('WooCommerce, ACF and more'); ?></span>
    			</div>
    			<div class="column cta">
    				<div class="button upgrade-button">
    					<span class="subscribe-button-text"><?php esc_html_e('Upgrade to Pro'); ?></span>
    				</div>
    				<span class="trusted"><?php esc_html_e('Trusted by over 200,000 happy users'); ?></span>
    			</div>
    		</div>
    		<ul class="perks">
    			<li class="guarantee"><span><?php esc_html_e('90 Day Guarantee'); ?></span><small><?php esc_html_e('No questions,<br> full refund.'); ?></small></li>
    			<li class="updates"><span><?php esc_html_e('Lifetime Updates'); ?></span><small><?php esc_html_e('Pay once and get<br>updates for life.'); ?></small></li>
    			<li class="support"><span><?php esc_html_e('World Class Support'); ?></span><small><?php esc_html_e('Get help from a team<br>of experts.'); ?></small></li>
    			<li class="license"><span><?php esc_html_e('Unlimited Sites'); ?></span><small><?php esc_html_e('Install on as many sites<br>as you like.'); ?></small></li>
    		</ul>
    		<p class="already-have"><a class="already-have-link"><?php esc_html_e('Already own the Pro version?'); ?></a></p>
        </div>
        <div class="install">
            <h1><?php esc_html_e('User Export Add-On is not installed'); ?></h1>
            <p><?php echo 'You can download the User Export Add-On from the customer portal: <a href="https://www.wpallimport.com/portal/">https://www.wpallimport.com/portal/</a>. Once you install it, you\'ll be able to export users.'; ?></p>
        </div>
	</div>
    <?php
    wp_die();
}