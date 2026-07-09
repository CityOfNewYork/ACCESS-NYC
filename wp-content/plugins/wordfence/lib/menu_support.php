<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
?>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
	<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner" data-prop-wants-disable-overlay="false"></div>
<?php endif; ?>
<?php
$support = @json_decode(wfConfig::get('supportContent'), true);
if (!is_array($support)) { $support = array(); }
?>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
				<?php
				echo wfView::create('common/section-title', array(
					'title' => __('Help', 'wordfence'),
					'showIcon' => true,
				))->render();
				?>
			</div>
			<div class="wf-col-xs-12">
				<div class="wf-block wf-active">
					<div class="wf-block-content">
						<ul class="wf-block-list">
							<li>
								<ul class="wf-block-list wf-block-list-horizontal">
									<li class="wf-flex-vertical">
									<?php if (wfLicense::current()->isPaidAndCurrent()): ?>
										<h3><?php esc_html_e('Premium Support', 'wordfence'); ?></h3>
										<p class="wf-center">
											<?php if (wfLicense::current()->isResponse()): ?>
												<?php esc_html_e('As a Wordfence Response customer you are entitled to hands-on priority support 24 hours a day 365 days a year. Our incident response team is available out of hours to handle urgent issues and security incidents. Our customer support team is available during business hours (Monday to Friday, 6am to 5pm Pacific and 9am to 8pm Eastern time) for product assistance. Both teams can sign-in to your site to assist, on request.', 'wordfence'); ?>
											<?php elseif (wfLicense::current()->isCare()): ?>
												<?php esc_html_e('As a Wordfence Care customer you are entitled to hands-on priority support and have access to our incident response team. Our senior support engineers and incident response team respond to requests quickly within business hours (Monday to Friday, 6am to 5pm Pacific and 9am to 8pm Eastern time) and can sign-in to your site on request to assist with complex issues.', 'wordfence'); ?>
											<?php else: ?>
												<?php esc_html_e('As a Wordfence Premium customer you\'re entitled to paid support via our ticketing system. Our senior support engineers respond to Premium tickets during regular business hours (Monday to Friday, 6am to 5pm Pacific and 9am to 8pm Eastern time) and have a direct line to our QA and development teams.', 'wordfence') ?>
											<?php endif ?>
										</p>
										<p>
											<a href="<?php echo esc_attr(wfLicense::current()->getSupportUrl('helpPageSupport')) ?>" target="_blank" rel="noopener noreferrer" class="wf-btn wf-btn-primary wf-btn-callout-subtle"><?php esc_html_e('Get Help', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a>
											<div>
											<?php if (wfLicense::current()->isBelowCare()): ?>
												<a href="https://www.wordfence.com/gnl1helpPageCare/products/wordfence-care/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Upgrade to hands-on support with Wordfence Care', 'wordfence') ?></a>
											<?php elseif (wfLicense::current()->isBelowResponse()): ?>
												<a href="https://www.wordfence.com/gnl1helpPageResponse/products/wordfence-response/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Upgrade to a 24/7 1-hour response time with Wordfence Response', 'wordfence') ?></a>
											<?php endif ?>
											</div>
										</p>
									<?php else: ?>
										<h3><?php esc_html_e('Upgrade Now to Access Premium Support', 'wordfence'); ?></h3>
										<p class="wf-center"><?php echo wp_kses(__('Our senior support engineers <strong>respond to Premium tickets within a few hours</strong> on average and have a direct line to our QA and development teams.', 'wordfence'), array('strong'=>array())); ?></p>
										<p><a href="https://www.wordfence.com/gnl1supportUpgrade/wordfence-signup/" target="_blank" rel="noopener noreferrer" class="wf-btn wf-btn-primary wf-btn-callout-subtle"><?php esc_html_e('Upgrade to Premium', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></p>
									<?php endif; ?>
									</li>
									<li class="wf-flex-vertical">
										<h3><?php esc_html_e('Free Support', 'wordfence'); ?></h3>
										<p class="wf-center"><?php echo wp_kses(__('Support for free customers is available via our forums page on wordpress.org. The majority of requests <strong>receive an answer within a few days.</strong>', 'wordfence'), array('strong'=>array())); ?></p>
										<p><a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_FREE); ?>" target="_blank" rel="noopener noreferrer" class="wf-btn wf-btn-default wf-btn-callout-subtle"><?php esc_html_e('Go to Support Forums', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></p>
									</li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wf-block wf-legacy<?php echo (wfPersistenceController::shared()->isActive('support-gdpr') ? ' wf-active' : ''); ?>" data-persistence-key="support-gdpr">
					<div class="wf-block-header">
						<div class="wf-block-header-content">
							<div class="wf-block-title">
								<strong><?php esc_html_e('GDPR Information', 'wordfence'); ?></strong>
							</div>
							<div class="wf-block-header-action"><div class="wf-block-header-action-disclosure wf-legacy" role="checkbox" aria-checked="<?php echo (wfPersistenceController::shared()->isActive('support-gdpr') ? 'true' : 'false'); ?>" tabindex="0"></div></div>
						</div>
					</div>
					<div class="wf-block-content">
						<ul class="wf-block-list">
							<li>
								<ul class="wf-option wf-option-static">
									<li class="wf-option-title">
										<ul class="wf-flex-vertical wf-flex-align-left">
											<li><?php esc_html_e('General Data Protection Regulation', 'wordfence'); ?> <a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_GDPR); ?>" target="_blank" rel="noopener noreferrer" class="wf-inline-help"><i class="wf-fa wf-fa-question-circle-o" aria-hidden="true"></i><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></li>
											<li class="wf-option-subtitle"><?php esc_html_e('The GDPR is a set of rules that provides more control over EU personal data. Defiant has updated its terms of service, privacy policies, and software, as well as made available standard contractual clauses to meet GDPR compliance.', 'wordfence'); ?></li>
										</ul>
									</li>
								</ul>
							</li>
							<li>
								<ul class="wf-option wf-option-static">
									<li class="wf-option-title">
										<ul class="wf-flex-vertical wf-flex-align-left">
											<li><?php esc_html_e('Agreement to New Terms and Privacy Policies', 'wordfence'); ?></li>
											<li class="wf-option-subtitle"><?php esc_html_e('To continue using Defiant products and services including the Wordfence plugin, all customers must review and agree to the updated terms and privacy policies. These changes reflect our commitment to follow data protection best practices and regulations. The Wordfence interface will remain disabled until these terms are agreed to.', 'wordfence'); ?></li>
										</ul>
									</li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div> <!-- end GDPR -->
		<?php if (isset($support['all'])): ?>
		<div class="wf-row">
			<div class="wf-col-xs-12 wf-col-sm-9 wf-col-sm-half-padding-right wf-add-top">
				<h3 class="wf-no-top"><?php esc_html_e('All Documentation', 'wordfence'); ?></h3>
			</div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12 wf-col-sm-3 wf-col-sm-push-9 wf-col-sm-half-padding-left">
				<div class="wf-block wf-active">
					<div class="wf-block-content">
						<div class="wf-support-top-block">
							<h4><?php esc_html_e('Top Topics and Questions', 'wordfence'); ?></h4>
							<ol>
							<?php
							if (isset($support['top'])):
								foreach ($support['top'] as $entry):
							?>
								<li><a href="<?php echo esc_url($entry['permalink']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($entry['title']); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></li>
							<?php
								endforeach;
							endif;
							?>
							</ol>
						</div>
					</div>
				</div>
			</div>
			<div class="wf-col-xs-12 wf-col-sm-9 wf-col-sm-pull-3 wf-col-sm-half-padding-right">
			<?php
			if (isset($support['all'])):
				foreach ($support['all'] as $entry):
			?>
				<div class="wf-block wf-active wf-add-bottom">
					<div class="wf-block-content">
						<div class="wf-support-block">
							<h4><a href="<?php echo esc_url($entry['permalink']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($entry['title']); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></h4>
							<p><?php echo esc_html($entry['excerpt']); ?></p>
							<?php if (isset($entry['children'])): ?>
							<ul>
							<?php foreach ($entry['children'] as $child): ?>
								<li><a href="<?php echo esc_url($child['permalink']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($child['title']); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></li>
							<?php endforeach; ?>
							</ul>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php
				endforeach;
			endif;
			?>
			</div>
		</div>
		<?php else: ?>
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wf-block wf-active">
					<div class="wf-block-content">
						<div class="wf-support-missing-block">
							<h4><?php esc_html_e('Documentation', 'wordfence'); ?></h4>
							<p><?php echo wp_kses(__('Documentation about Wordfence may be found on our website by clicking the button below or by clicking the <i class="wf-fa wf-fa-question-circle-o" aria-hidden="true"></i> links on any of the plugin\'s pages.', 'wordfence'), array('i'=>array('class'=>array(), 'aria-hidden'=>array()))); ?></p>
							<p class="wf-no-bottom"><a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_INDEX); ?>" target="_blank" rel="noopener noreferrer" class="wf-btn wf-btn-default wf-btn-callout-subtle"><?php esc_html_e('View Documentation', 'wordfence'); ?><span class="screen-reader-text"> (<?php esc_html_e('opens in new tab', 'wordfence') ?>)</span></a></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="OnboardingModals"></div>
