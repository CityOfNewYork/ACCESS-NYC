<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents an issue template.
 */
echo wfView::create('scanner/text/issue-base', array(
	'internalType' => 'postBadTitle',
	'displayType' => __('Post', 'wordfence'),
	'textOutput' => (isset($textOutput) ? $textOutput : null),
	'textOutputDetailPairs' => array(
		__('Title', 'wordfence') => '$data.postTitle',
		__('Posted on', 'wordfence') => '$data.postDate',
		null,
		__('Details', 'wordfence') => '$longMsg',
		null,
		__('Multisite Blog ID', 'wordfence') => array('$data.isMultisite', '$data.blog_id'),
		__('Multisite Blog Domain', 'wordfence') => array('$data.isMultisite', '$data.domain'),
		__('Multisite Blog Path', 'wordfence') => array('$data.isMultisite', '$data.path'),
	),
))->render();