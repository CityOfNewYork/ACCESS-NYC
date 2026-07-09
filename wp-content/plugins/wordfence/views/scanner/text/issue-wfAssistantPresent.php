<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'wfAssistantPresent',
	'displayType' => __('Wordfence Assistant Present', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => [
		__("Plugin Name", "wordfence") => "Wordfence Assistant",
		null,
		__("Details", "wordfence") => '$longMsg'
	]
))->render();