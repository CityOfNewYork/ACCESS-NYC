<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents multiple boolean options under a single heading with a checkbox toggle control for each.
 *
 * @var array $options The options shown. The structure is defined below. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $title The overall title shown for the options. Required.
 * @var string $helpLink The link to the corresponding external help page. Optional.
 * @var bool $wrap Whether or not the options should be allowed to wrap. Optional, defaults to false.
 * 
 * $options is an array of
 * 	array(
 * 		'name' => string <option name>,
 * 		'enabledValue' => string <value saved if the toggle is enabled>,
 * 		'disabledValue' => string <value saved if the toggle is disabled>,
 * 		'value' => string <current value of the option>,
 * 		'title' => string|\Wordfence2FA\Text\Model_HTML <title displayed to label the checkbox>,
 * 		'editable' => bool Whether or not the option can be edited, defaults to true.
 * 	)
 */
?>
<ul class="wfls-option wfls-option-toggled-multiple">
	<li class="wfls-option-title"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title); ?><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?></li>
	<li class="wfls-option-checkboxes<?php if (isset($wrap) && $wrap) { echo ' wfls-option-checkboxes-wrap'; } ?>">
		<?php
		foreach ($options as $o):
			$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $o['name']);
		?>
		<ul id="<?php echo esc_attr($id); ?>" data-option="<?php echo esc_attr($o['name']); ?>" data-enabled-value="<?php echo esc_attr($o['enabledValue']); ?>" data-disabled-value="<?php echo esc_attr($o['disabledValue']); ?>" data-original-value="<?php echo esc_attr($o['value'] == $o['enabledValue'] ? $o['enabledValue'] : $o['disabledValue']); ?>">
			<li class="wfls-option-checkbox<?php echo ($o['value'] == $o['enabledValue'] ? ' wfls-checked' : ''); ?><?php echo (isset($o['editable']) && !$o['editable'] ? ' wfls-disabled' : ''); ?>" role="checkbox" aria-checked="<?php echo ($o['value'] == $o['enabledValue'] ? 'true' : 'false'); ?>" tabindex="0" aria-labelledby="<?php echo esc_attr($id); ?>-label"><i class="wfls-ion-ios-checkmark-empty" aria-hidden="true"></i></li>
			<li id="<?php echo esc_attr($id); ?>-label" class="wfls-option-title"><?php echo esc_html($o['title']); ?></li>
		</ul>
		<?php endforeach; ?>
	</li>
</ul>