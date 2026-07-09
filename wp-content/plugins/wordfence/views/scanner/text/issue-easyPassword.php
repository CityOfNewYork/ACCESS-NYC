<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'easyPassword',
	'displayType' => __('Insecure Password', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Login Name', 'wordfence') => '$data.user_login',
		__('User Email', 'wordfence') => '$data.user_email',
		__('Full Name', 'wordfence') => '$data.first_name $data.last_name',
		null,
		__('Details', 'wordfence') => '$longMsg',
	),
))->render();