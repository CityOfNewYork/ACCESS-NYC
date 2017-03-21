<?php
function pmai_pmxi_save_options($post)
{
	if (PMXI_Plugin::getInstance()->getAdminCurrentScreen()->action == 'options')
	{
		if ($post['update_acf_logic'] == 'only'){
			$post['acf_list'] = explode(",", $post['acf_only_list']); 
		}
		elseif ($post['update_acf_logic'] == 'all_except'){
			$post['acf_list'] = explode(",", $post['acf_except_list']); 	
		}
	}	
	return $post;
}
