<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
?>
<div class="wfls-save-banner wfls-nowrap wfls-padding-add-right-responsive">
	<a href="#" id="wfls-cancel-changes" class="wfls-btn wfls-btn-sm wfls-btn-default wfls-disabled"><?php echo wp_kses(/* translators: word order may be reversed as long as HTML remains around "Changes" */ __('Cancel<span class="wfls-visible-sm-inline"> Changes</span>', 'wordfence-login-security'), array('span'=>array('class'=>array()))); ?></a>&nbsp;&nbsp;<a href="#" id="wfls-save-changes" class="wfls-btn wfls-btn-sm wfls-btn-primary wfls-disabled"><?php echo wp_kses(/* translators: word order may be reversed as long as HTML remains around "Changes" */ __('Save<span class="wfls-visible-sm-inline"> Changes</span>', 'wordfence-login-security'), array('span'=>array('class'=>array()))); ?></a>
</div>
<div id="wfls-settings" class="wfls-flex-row wfls-flex-row-wrappable wfls-flex-row-equal-heights">
	<!-- begin status content -->
	<div id="wfls-user-stats" class="wfls-flex-row wfls-flex-row-equal-heights wfls-flex-item-xs-100">
		<?php
			echo \WordfenceLS\Model_View::create('settings/user-stats', array(
				'counts' => \WordfenceLS\Controller_Users::shared()->get_detailed_user_counts_if_enabled(),
			))->render();
		?>
	</div>
	<!-- end status content -->
	<!-- begin options content -->
	<div id="wfls-options">
		<?php
		echo \WordfenceLS\Model_View::create('settings/options', array(
			'hasWoocommerce' => $hasWoocommerce
		))->render();
		?>
	</div>
	<!-- end options content -->
</div>