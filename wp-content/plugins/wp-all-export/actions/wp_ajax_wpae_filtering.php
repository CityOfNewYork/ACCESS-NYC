<?php

function pmxe_wp_ajax_wpae_filtering(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	if ( ! current_user_can( PMXE_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	$addons = new \Wpae\App\Service\Addons\AddonService();
	$response = array(
		'html' => '',
		'btns' => ''
	);

	ob_start();

	$errors = new WP_Error();	

	$input = new PMXE_Input();
	
	$post = $input->post('data', array());

	if ( ! empty($post['cpt'])):		

		$engine = new XmlExportEngine($post, $errors);	

		$engine->init_available_data();	

		?>
		<div class="wpallexport-content-section">
			<div class="wpallexport-collapsed-header">
				<h3><?php _e('Add Filtering Options', 'wp_all_export_plugin'); ?></h3>	
			</div>		
			<div class="wpallexport-collapsed-content">			
				<?php include_once PMXE_ROOT_DIR . '/views/admin/export/blocks/filters.php'; ?>
			</div>	
		</div>

	<?php

	endif;

	$response['html'] = ob_get_clean();

	if ( (XmlExportEngine::$is_user_export && $post['cpt'] != 'shop_customer' && !$addons->isUserAddonActive()) || XmlExportEngine::$is_comment_export || XmlExportEngine::$is_taxonomy_export || $post['cpt'] == 'shop_customer' )
	{
		$response['btns'] = '';
		exit(json_encode($response)); die;
	}
	
	ob_start();

	if ( XmlExportEngine::$is_auto_generate_enabled ):
	?>
    <div class="wpallexport-free-edition-notice" id="migrate-orders-notice" style="padding: 20px; margin-bottom: 10px; display: none;">
        <a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=2707173&edd_options%5Bprice_id%5D=1&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=migrate-orders"><?php _e('Upgrade to the Pro edition of WP All Export to Migrate Orders', PMXE_Plugin::LANGUAGE_DOMAIN);?></a>
        <p><?php _e('If you already own it, remove the free edition and install the Pro edition.', PMXE_Plugin::LANGUAGE_DOMAIN);?></p>
    </div>

        <div class="wpallexport-free-edition-notice" id="migrate-users-notice" style="padding: 20px; margin-bottom: 10px; display: none;">
            <a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=2707173&edd_options%5Bprice_id%5D=1&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=migrate-users"><?php _e('Upgrade to the Pro edition of WP All Export to Migrate Users', PMXE_Plugin::LANGUAGE_DOMAIN);?></a>
            <p><?php _e('If you already own it, remove the free edition and install the Pro edition.', PMXE_Plugin::LANGUAGE_DOMAIN);?></p>
        </div>

    <?php if(isset($post['cpt'])) { ?>
        <span class="wp_all_export_btn_with_note">
            <a href="javascript:void(0);" class="back rad3 auto-generate-template" style="float:none; background: #425f9a; padding: 0 50px; margin-right: 10px; color: #fff; font-weight: normal;"><?php printf(__('Migrate %s', 'wp_all_export_plugin'), wp_all_export_get_cpt_name(array($post['cpt']), 2, $post)); ?></a>
            <span class="auto-generate-template">&nbsp;</span>
        </span>
    <?php } ?>
	<span class="wp_all_export_btn_with_note">
		<input type="submit" class="button button-primary button-hero wpallexport-large-button" value="<?php _e('Customize Export File', 'wp_all_export_plugin') ?>"/>
		<span class="auto-generate-template">&nbsp;</span>
	</span>
	<?php
	else:
	?>	
	<span class="wp_all_export_btn_with_note">
		<input type="submit" class="button button-primary button-hero wpallexport-large-button" value="<?php _e('Customize Export File', 'wp_all_export_plugin') ?>"/>		
	</span>
	<?php
	endif;
	$response['btns'] = ob_get_clean();
	
	exit(json_encode($response)); die;

}