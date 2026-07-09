<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Displays the Site Cleaning lower prompt.
 */
?>
<div id="wf-site-cleaning-bottom" class="wf-block wf-add-top-small wf-active">
	<div class="wf-block-content">
		<ul class="wf-block-list">
			<li>
				<div class="wf-flex-vertical">
					<h3 class="wf-center"><?php esc_html_e('Need help from the WordPress security experts?', 'wordfence'); ?></h3>
					<?php if (wfLicense::current()->isBelowCare()): ?>
						<p class="wf-center wf-no-top"><?php esc_html_e('Sign up for Wordfence Care today and get expert help with any security issue. Wordfence Care is for business owners who put a premium on their time. With Wordfence Care, we take care of it, so that you can focus on your customers. Check out Wordfence Response for mission-critical WordPress websites.', 'wordfence'); ?></p>
					<?php endif ?>
					<p class="wf-center wf-add-bottom">
						<?php if (wfLicense::current()->isBelowCare()): ?>
							<a class="wf-btn wf-btn-primary wf-btn-callout-subtle" href="https://www.wordfence.com/gnl1scanLowerAd/products/wordfence-care/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More About Wordfence Care', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
							<a class="wf-btn wf-btn-primary wf-btn-callout-subtle" href="https://www.wordfence.com/gnl1scanLowerAd/products/wordfence-response/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More About Wordfence Response', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
						<?php else: ?>
							<a class="wf-btn wf-btn-primary wf-btn-callout-subtle" href="<?php echo esc_attr(wfLicense::current()->getSupportUrl('scanLowerAd')) ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Get Help', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
						<?php endif ?>
					</p>
				</div>
			</li>
		</ul>
	</div>
</div>