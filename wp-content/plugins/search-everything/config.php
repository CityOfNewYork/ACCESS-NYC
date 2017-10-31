<?php

global $se_options, $se_meta, $se_global_notice_pages;
$se_options = false;
$se_meta = false;

$se_global_notice_pages = array('plugins.php', 'index.php', 'update-core.php');

$se_response_messages = array(
	SE_PREFS_STATE_NOT_ENGLISH => __('<a href="http://support.zemanta.com/customer/portal/articles/674752-which-languages-does-zemanta-support" target="_blank">Your blog is not in English</a>', 'SearchEverything'),
	SE_PREFS_STATE_EMPTY => __('Your blog feed is empty', 'SearchEverything'),
	SE_PREFS_STATE_FAILED => __('Unable to reslove URL to a source feed' , 'SearchEverything'),
	SE_PREFS_STATE_FOUND => __('You are connected to Zemanta network', 'SearchEverything')
);

function se_get_response_messages() {
	global $se_response_messages;
	return $se_response_messages;
}

function se_get_options() {
	global $se_options, $se_meta;
	if($se_options) {
		return $se_options;
	}

	$se_options = get_option('se_options', false);

	if(!$se_options || $se_meta['version'] !== SE_VERSION) {
		se_upgrade();
		$se_meta = get_option('se_meta');
		$se_options = get_option('se_options');
	}

	$se_meta = new ArrayObject($se_meta);
	$se_options = new ArrayObject($se_options);

	return $se_options;
}


function se_get_meta() {
	global $se_meta;

	if (!$se_meta) {
		se_get_options();
	}
	return $se_meta;
}

function se_update_meta($new_meta) {
	global $se_meta;

	$new_meta = (array) $new_meta;

	$r = update_option('se_meta', $new_meta);

	if($r && $se_meta !== false) {
		$se_meta->exchangeArray($new_meta);
	}

	return $r;
}

function se_update_options($new_options) {
	global $se_options;

	$new_options = (array) $new_options;
	$r = update_option('se_options', $new_options);
	if($r && $se_options !== false) {
		$se_options->exchangeArray($new_options);
	}

	return $r;
}

function se_set_global_notice() {
	$url = 'http://zem.si/1l7q5KS';
	$se_meta = get_option('se_meta', false);
	$se_meta['se_global_notice'] = array(
		'title' => 'Searching for your car keys?',
		'message' => 'Well, there are some things our plugin can\'t search for - your car keys, your wallet, a soulmate and <strong>unregistered custom post types</strong> :) <br> It searches for almost everything else, but it also does some other amazing stuff, like ... research. <a href="'.$url.'" target="_blank">Check it out!</a>'
	);
	se_update_meta($se_meta);
}

//we have to be careful, as previously version was not stored in the options!
function se_upgrade() {
	$se_meta = get_option('se_meta', false);
	$version = false;

	if($se_meta) {
		$version = $se_meta['version'];
	}

	if($version) {
		if(version_compare($version, SE_VERSION, '<')) {
			call_user_func('se_migrate_' . str_replace('.', '_', $version));
			se_upgrade();
		}
	} else {
		//check if se_options exist
		$se_options = get_option('se_options', false);
		if($se_options) {
			se_migrate_7_0_1(); //existing users don't have version stored in their db
		} else {
			se_install();
		}
	}
}

function se_migrate_8_1_8() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.9';
	update_option('se_meta', $se_meta);
}

function se_migrate_8_1_7() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.8';
	update_option('se_meta', $se_meta);
}

function se_migrate_8_1_6() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.7';
	update_option('se_meta', $se_meta);
}

function se_migrate_8_1_5() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.6';
	update_option('se_meta', $se_meta);
}

function se_migrate_8_1_4() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.5';
	update_option('se_meta', $se_meta);
}

function se_migrate_8_1_3() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.4';
	update_option('se_meta', $se_meta);
}


function se_migrate_8_1_2() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.3';
	update_option('se_meta', $se_meta);
}


function se_migrate_8_1_1() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.2';
	update_option('se_meta', $se_meta);
}


function se_migrate_8_1() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1.1';
	update_option('se_meta', $se_meta);
}

function se_migrate_8_0() {
	$se_meta = get_option('se_meta', false);
	$se_meta['version'] = '8.1';
	update_option('se_meta', $se_meta);

	$se_options = get_option('se_options', false);
	if (empty($se_options['se_research_metabox'])) {
		$se_options['se_research_metabox'] = array (
			'visible_on_compose'		=> true,
			'external_search_enabled'	=> false,
			'notice_visible'			=> true,
		);
	}
	update_option('se_options',$se_options);

	se_set_global_notice();
}

function se_migrate_7_0_4() {
	$se_meta = get_option('se_meta', false);

	if ($se_meta) {
		$se_meta['version'] = '8.0';
		$se_meta['api_key'] = false;
	}

	$se_options = get_option('se_options', false);

	//enable external search
	$se_options['se_research_metabox'] = array (
		'visible_on_compose'		=> true,
		'external_search_enabled'	=> false,
		'notice_visible'			=> true,
		);
	$se_meta['show_options_page_notice'] = false;

	update_option('se_meta',$se_meta);
	update_option('se_options',$se_options);
}

function se_migrate_7_0_3() {

	$se_meta = get_option('se_meta', false);

	if ($se_meta) {
		$se_meta['version'] = '7.0.4';
	}
	update_option('se_meta',$se_meta);
}


function se_migrate_7_0_2() {

	$se_meta = get_option('se_meta', false);

	if ($se_meta) {
		$se_meta['version'] = '7.0.3';
	}
	update_option('se_meta',$se_meta);
}


function se_migrate_7_0_1() {
	$se_meta = array(
		'blog_id'			=> false,
		'auth_key'			=> false,
		'version'			=> '7.0.2',
		'first_version'			=> '7.0.1',
		'new_user'			=> false,
		'name'				=> '',
		'email'				=> '',
		'show_options_page_notice'	=> false
	);

	update_option('se_meta',$se_meta);

	//get options and update values to boolean
	$old_options = get_option('se_options', false);

	if($old_options) {
		$new_options = se_get_default_options();

		$boolean_keys = array(
			'se_use_page_search'		=> false,
			'se_use_comment_search' 	=> false,
			'se_use_tag_search'		=> false,
			'se_use_tax_search'		=> false,
			'se_use_category_search'	=> false,
			'se_approved_comments_only'=> false,
			'se_approved_pages_only'	=> false,
			'se_use_excerpt_search'	=> false,
			'se_use_draft_search'		=> false,
			'se_use_attachment_search'	=> false,
			'se_use_authors'		=> false,
			'se_use_cmt_authors'		=> false,
			'se_use_metadata_search'	=> false,
			'se_use_highlight'		=> false,
			);
		$text_keys = array(
			'se_exclude_categories' 	=> '',
			'se_exclude_categories_list'	=> '',
			'se_exclude_posts'		=> '',
			'se_exclude_posts_list'		=> '',
			'se_highlight_color'		=> '',
			'se_highlight_style'		=> ''
			);

		foreach ($boolean_keys as $k) {
			$new_options[$k] = ('Yes' === $old_options[$k]);
		}
		foreach ($text_keys as $t) {
			$new_options[$t] = $old_options[$t];
		}
		update_option('se_options',$new_options);
	}

	//moved to meta
	$notice = get_option('se_show_we_tried', false);
	if($notice) {
		delete_option('se_show_we_tried');
	}
}


function se_install() {
	$se_meta = array(
		'blog_id' => false,
		'api_key' => false,
		'auth_key' => false,
		'version' => SE_VERSION,
		'first_version' => SE_VERSION,
		'new_user' => true,
		'name' => '',
		'email' => '',
		'show_options_page_notice'	=> false
	);
	$se_options = se_get_default_options();

	update_option('se_meta', $se_meta);
	update_option('se_options', $se_options);

	se_set_global_notice();
}

function se_get_default_options() {
	$se_options = array(
				'se_exclude_categories'	=> '',
				'se_exclude_categories_list' 	=> '',
				'se_exclude_posts'		=> '',
				'se_exclude_posts_list'		=> '',
				'se_use_page_search'		=>false,
				'se_use_comment_search' 	=>true,
				'se_use_tag_search'		=> false,
				'se_use_tax_search'		=> false,
				'se_use_category_search'	=> true,
				'se_approved_comments_only'=> true,
				'se_approved_pages_only'	=> false,
				'se_use_excerpt_search'	=> false,
				'se_use_draft_search'		=> false,
				'se_use_attachment_search'	=> false,
				'se_use_authors'		=> false,
				'se_use_cmt_authors'		=> false,
				'se_use_metadata_search'	=> false,
				'se_use_highlight'		=> true,
				'se_highlight_color'		=> 'orange',
				'se_highlight_style'		=> '',
				'se_research_metabox'		 	=> array (
					'visible_on_compose'		=> true,
					'external_search_enabled'	=> false,
					'notice_visible'			=> true
				)
			);

	return $se_options;
}

