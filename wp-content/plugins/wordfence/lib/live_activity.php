<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<div class="wf-live-activity" data-mode="auto">
	<div class="wf-live-activity-inner">
		<div class="wf-live-activity-content">
			<div class="wf-live-activity-title"><?php esc_html_e('Wordfence Live Activity:', 'wordfence') ?></div>
			<div class="wf-live-activity-message"></div>
		</div>
		<?php if (wfConfig::get('liveActivityPauseEnabled')): ?>
		<div class="wf-live-activity-state"><p><?php esc_html_e('Live Updates Paused &mdash; Click inside window to resume', 'wordfence') ?></p></div>
		<?php endif; ?>
	</div>
</div>