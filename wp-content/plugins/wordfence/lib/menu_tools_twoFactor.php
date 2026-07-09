<?php
if (!defined('WORDFENCE_VERSION')) { exit; }

$helpLink = wfSupportController::supportURL(wfSupportController::ITEM_TOOLS_TWO_FACTOR);

if (function_exists('network_admin_url') && is_multisite()) {
	$lsModuleURL = network_admin_url('admin.php?page=WFLS');
}
else {
	$lsModuleURL = admin_url('admin.php?page=WFLS');
}

echo wfView::create('common/section-title', array(
	'title'     => __('Two-Factor Authentication', 'wordfence'),
	'helpLink'  => $helpLink,
	'helpLabelHTML' => wp_kses(__('Learn more<span class="wf-hidden-xs"> about Two-Factor Authentication</span>', 'wordfence'), array('span'=>array('class'=>array()))),
))->render();
?>

<script type="application/javascript">
	(function($) {
		$(function() {
			document.title = "<?php esc_attr_e('Two-Factor Authentication', 'wordfence'); ?>" + " \u2039 " + WFAD.basePageName;
		});
	})(jQuery);
</script>

<div id="wordfenceMode_twoFactor"></div>

<div class="wordfence-vue-wrapper" data-base-component="TwoFactorNotice"></div>
