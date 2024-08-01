<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents a switch option.
 *
 * @var string $optionName The option name for the switch. Required.
 * @var string $value The current value of $optionName. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $title The title shown for the option. Required.
 * @var array $states An array of the possible states for the switch. The array matches the format array('value' => <value>, 'label' => <label>) Required.
 * @var string $helpLink If defined, the link to the corresponding external help page. Optional.
 * @var string $alignment If defined, controls the alignment of the switch control. Optional.
 */

$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $optionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-switch" data-option-name="<?php echo esc_attr($optionName); ?>" data-original-value="<?php echo esc_attr($value); ?>">
	<?php if (!isset($noSpacer) || !$noSpacer): ?>
	<li class="wfls-option-spacer"></li>
	<?php endif; ?>
	<li class="wfls-option-content wfls-no-right">
		<ul>
			<li class="wfls-option-title">
			<?php if (isset($subtitle)): ?>
				<ul class="wfls-flex-vertical wfls-flex-align-left">
					<li>
			<?php endif; ?>
						<span id="<?php echo esc_attr($id); ?>-label"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title); ?></span><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?>
			<?php if (isset($subtitle)): ?>
					</li>
					<li class="wfls-option-subtitle"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($subtitle); ?></li>
				</ul>
			<?php endif; ?>
			</li>
			<li class="wfls-option-switch<?php if (isset($alignment)) { echo ' ' . $alignment; } ?> wfls-padding-add-top-xs-small">
				<ul class="wfls-switch" role="radiogroup" aria-labelledby="<?php echo esc_attr($id); ?>-label">
				<?php foreach ($states as $s): ?>
					<li<?php if ($s['value'] == $value) { echo ' class="wfls-active"'; } ?> data-option-value="<?php echo esc_attr($s['value']); ?>" role="radio" aria-checked="<?php echo ($s['value'] == $value ? 'true' : 'false'); ?>" tabindex="0"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($s['label']); ?></li>
				<?php endforeach; ?>
				</ul>
			</li>
		</ul>
	</li>
</ul>