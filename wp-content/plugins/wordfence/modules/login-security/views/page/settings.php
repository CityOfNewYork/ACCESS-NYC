<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
?>
<div class="wfls-save-banner wfls-nowrap wfls-padding-add-right-responsive wordfence-vue-wrapper" data-base-component="WFLSSettingsButtons"></div>
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
	<div id="wfls-options" class="wordfence-vue-wrapper" data-base-component="WFLSOptions"></div>
	<!-- end options content -->
</div>
