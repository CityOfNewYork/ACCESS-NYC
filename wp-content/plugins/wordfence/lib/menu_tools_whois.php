<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
?>
<script type="application/javascript">
	(function($) {
		$(function() {
			document.title = "<?php esc_attr_e('Whois Lookup', 'wordfence'); ?>" + " \u2039 " + WFAD.basePageName;
		});
	})(jQuery);
</script>

<div class="wordfenceModeElem" id="wordfenceMode_whois"></div>

<div id="wf-tools-whois">
	<div class="wf-section-title">
		<h2><?php esc_html_e('Whois Lookup', 'wordfence') ?></h2>
		<span><?php echo wp_kses(sprintf(
				/* translators: URL to support page. */
				__('<a href="%s" target="_blank" rel="noopener noreferrer" class="wf-help-link">Learn more<span class="wf-hidden-xs"> about Whois Lookup</span><span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_TOOLS_WHOIS_LOOKUP)), array('a'=>array('href'=>array(), 'target'=>array(), 'rel'=>array(), 'class'=>array()), 'span'=>array('class'=>array()))); ?>
			<i class="wf-fa wf-fa-external-link" aria-hidden="true"></i></span>
	</div>

	<p><?php esc_html_e("The whois service gives you a way to look up who owns an IP address or domain name that is visiting your website or is engaging in malicious activity on your website.", 'wordfence') ?></p>

	<div class="wordfence-vue-wrapper" data-base-component="WhoisForm"></div>
</div>
