<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents a modal prompt.
 *
 * @var string|\WordfenceLS\Text\Model_HTML $title The title for the prompt. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $message The message for the prompt. Required.
 * @var array $primaryButton The parameters for the primary button. The array is in the format array('id' => <element id>, 'label' => <button text>, 'link' => <href value>). Optional.
 * @var array $secondaryButtons The parameters for any secondary buttons. It is an array of arrays in the format array('id' => <element id>, 'label' => <button text>, 'link' => <href value>). The ordering of entries is the right-to-left order the buttons will be displayed. Optional.
 */

$titleHTML = \WordfenceLS\Text\Model_HTML::esc_html($title);
$messageHTML = \WordfenceLS\Text\Model_HTML::esc_html($message);
$embedded = isset($embedded) ? $embedded : false;

if (!isset($secondaryButtons)) {
	$secondaryButtons = array();
}
$secondaryButtons = array_reverse($secondaryButtons);
?>
<div class="wfls-modal">
	<div class="wfls-modal-header">
		<div class="wfls-modal-header-content">
			<div class="wfls-modal-title">
				<strong><?php echo $titleHTML; ?></strong>
			</div>
		</div>
		<div class="wfls-modal-header-action">
			<div class="wfls-padding-add-left-small wfls-modal-header-action-close"><a href="#" onclick="WFLS.panelClose(); return false"><i class="<?php echo (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-times-circle' : 'wfls-fa wfls-fa-times-circle'); ?>" aria-hidden="true"></i></a></div>
		</div>
	</div>
	<div class="wfls-modal-content">
		<?php echo $messageHTML; ?>
	</div>
	<div class="wfls-modal-footer">
		<ul class="wfls-flex-horizontal wfls-flex-align-right wfls-full-width">
			<?php foreach ($secondaryButtons as $button): ?>
				<li class="wfls-padding-add-left-small"><a href="<?php echo esc_url($button['link']); ?>" class="wfls-btn <?php echo isset($button['type']) ? $button['type'] : 'wfls-btn-default'; ?> wfls-btn-callout-subtle" id="<?php echo esc_attr($button['id']); ?>"><?php echo isset($button['labelHTML']) ? $button['labelHTML'] : esc_html($button['label']); ?></a></li>
			<?php endforeach; ?>
			<?php if (isset($primaryButton) && is_array($primaryButton)): ?>
				<li class="wfls-padding-add-left-small"><a href="<?php echo esc_url($primaryButton['link']); ?>" class="wfls-btn <?php echo isset($primaryButton['type']) ? $primaryButton['type'] : 'wfls-btn-primary'; ?> wfls-btn-callout-subtle" id="<?php echo esc_attr($primaryButton['id']); ?>"><?php echo isset($primaryButton['labelHTML']) ? $primaryButton['labelHTML'] : esc_html($primaryButton['label']); ?></a></li>
			<?php endif ?>
		</ul>
	</div>
</div>