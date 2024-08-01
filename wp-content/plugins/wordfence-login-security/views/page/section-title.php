<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WordfenceLS\Page\Model_Title $title The page title parameters.
 * @var bool $showIcon Whether or not to show the header icon. Optional, defaults to false.
 */
?>
<div class="wfls-section-title">
	<?php if (isset($showIcon) && $showIcon): ?>
		<div class="wfls-header-icon wfls-hidden-xs"></div>
	<?php endif; ?>
	<h2 class="wfls-center-xs" id="section-title-<?php echo esc_attr($title->id); ?>"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->title); ?></h2>
	<?php if ($title->helpURL !== null && $title->helpLink !== null): ?>
		<span class="wfls-hidden-xs"><a href="<?php echo esc_url($title->helpURL); ?>" target="_blank" rel="noopener noreferrer" class="wfls-help-link"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title->helpLink); ?> <i class="<?php echo (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-external-link' : 'wfls-fa wfls-fa-external-link'); ?>" aria-hidden="true"></i></a></span>
	<?php endif; ?>
</div>