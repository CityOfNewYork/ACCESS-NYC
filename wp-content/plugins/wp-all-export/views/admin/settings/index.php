<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<style type="text/css">
    .wpae-shake {
        -webkit-animation: wpae_shake 0.4s 1 linear;
        -moz-animation: wpae_shake 0.4s 1 linear;
        -o-animation: wpae_shake 0.4s 1 linear;
    }
    @-webkit-keyframes wpae_shake {
        0% { -webkit-transform: translate(30px); }
        20% { -webkit-transform: translate(-30px); }
        40% { -webkit-transform: translate(15px); }
        60% { -webkit-transform: translate(-15px); }
        80% { -webkit-transform: translate(8px); }
        100% { -webkit-transform: translate(0px); }
    }
    @-moz-keyframes wpae_shake {
        0% { -moz-transform: translate(30px); }
        20% { -moz-transform: translate(-30px); }
        40% { -moz-transform: translate(15px); }
        60% { -moz-transform: translate(-15px); }
        80% { -moz-transform: translate(8px); }
        100% { -moz-transform: translate(0px); }
    }
    @-o-keyframes wpae_shake {
        0% { -o-transform: translate(30px); }
        20% { -o-transform: translate(-30px); }
        40% { -o-transform: translate(15px); }
        60% { -o-transform: translate(-15px); }
        80% { -o-transform: translate(8px); }
        100% { -o-origin-transform: translate(0px); }
    }
</style>

<form class="settings" method="post" action="<?php echo esc_url($this->baseUrl); ?>" enctype="multipart/form-data">

    <div class="wpallexport-header">
		<div class="wpallexport-logo"></div>
		<div class="wpallexport-title">
			<h3><?php esc_html_e('Settings', 'wp_all_export_plugin'); ?></h3>
		</div>
	</div>
	<h2 style="padding:0px;"></h2>

    <div class="wpallexport-setting-wrapper">
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>

		<h3><?php esc_html_e('Import/Export Templates', 'wp_all_export_plugin') ?></h3>
		<?php $templates = new PMXE_Template_List(); $templates->getBy()->convertRecords() ?>
		<?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>
		<?php if ($templates->total()): ?>
			<table>
				<?php foreach ($templates as $t): ?>
					<tr>
						<td>
							<label class="selectit" for="template-<?php echo $t->id ?>"><input id="template-<?php echo esc_attr($t->id) ?>" type="checkbox" name="templates[]" value="<?php echo esc_attr($t->id) ?>" /> <?php echo wp_all_export_clear_xss(esc_html($t->name)); ?></label>
						</td>
					</tr>
				<?php endforeach ?>
			</table>
			<p class="submit-buttons">
				<input type="submit" class="button-primary" name="delete_templates" value="<?php esc_html_e('Delete Selected', 'wp_all_export_plugin') ?>" />
				<input type="submit" class="button-primary" name="export_templates" value="<?php esc_html_e('Export Selected', 'wp_all_export_plugin') ?>" />
			</p>
		<?php else: ?>
			<em><?php esc_html_e('There are no templates saved', 'wp_all_export_plugin') ?></em>
		<?php endif ?>
		<p>
			<input type="hidden" name="is_templates_submitted" value="1" />
			<input type="file" name="template_file"/>
			<input type="submit" class="button-primary" name="import_templates" value="<?php esc_html_e('Import Templates', 'wp_all_export_plugin') ?>" />
		</p>
	</div>

</form>
<br />

<form name="settings" class="settings" method="post" action="<?php echo esc_url($this->baseUrl); ?>">

	<h3><?php esc_html_e('Files', 'wp_all_export_plugin') ?></h3>
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label><?php esc_html_e('Secure Mode', 'wp_all_export_plugin'); ?></label></th>
				<td>
					<fieldset style="padding:0;">
						<legend class="screen-reader-text"><span><?php esc_html_e('Secure Mode', 'wp_all_export_plugin'); ?></span></legend>
						<input type="hidden" name="secure" value="0"/>
						<label for="secure"><input type="checkbox" value="1" id="secure" name="secure" <?php echo (($post['secure']) ? 'checked="checked"' : ''); ?>><?php esc_html_e('Randomize folder names', 'wp_all_export_plugin'); ?></label>
					</fieldset>														
					<p class="description">
						<?php
							$wp_uploads = wp_upload_dir();
						?>
						<?php printf('If enabled, exported files and temporary files will be saved in a folder with a randomized name in %s.<br/><br/>If disabled, exported files will be saved in the Media Library.', esc_html($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY) ); ?>
					</p>
                    <p class="submit-buttons">
                        <?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
                        <input type="hidden" name="is_settings_submitted" value="1" />
                        <input type="submit" class="button-primary" value="Save Settings" />
                    </p>
				</td>
			</tr>			
		</tbody>
	</table>

	<h3><?php esc_html_e('Zapier Integration', 'wp_all_export_plugin') ?></h3>
	
	<table class="form-table">
		<tbody>	
			<tr>
				<th scope="row"><label><?php esc_html_e('Getting Started', 'wp_all_export_plugin'); ?></label></th>
				<td>					
					<p class="description"><?php printf(wp_kses_post(__('Zapier acts as a middle man between WP All Export and hundreds of other popular apps. To get started go to Zapier.com, create an account, and make a new Zap. Read more: <a target="_blank" href="https://zapier.com/zapbook/wp-all-export-pro/">https://zapier.com/zapbook/wp-all-export-pro/</a>', 'wp_all_export_plugin'), "https://zapier.com/zapbook/wp-all-export-pro/")); ?></p>
				</td>
			</tr>			
			<tr>
				<th scope="row"><label><?php esc_html_e('API Key', 'wp_all_export_plugin'); ?></label></th>
				<td>
					<input type="text" class="regular-text" name="zapier_api_key" readOnly="readOnly" value=""/>
					<input type="submit" class="button-secondary generate-zapier-api-key" name="pmxe_generate_zapier_api_key" value="<?php esc_html_e('Generate API Key', 'wp_all_export_plugin'); ?>"/>
					<p class="description"><?php esc_html_e('Changing the key will require you to update your existing Zaps on Zapier.', 'wp_all_export_plugin'); ?></p>
				</td>
			</tr>											
		</tbody>
	</table>	

	<div class="wpallexport-free-edition-notice zapier-upgrade" style="margin: 15px 0; padding: 20px; display: none;">
		<a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=2707173&edd_options%5Bprice_id%5D=1&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=zapier"><?php esc_html_e('Upgrade to the Pro edition of WP All Export for Zapier Integration','wp_all_export_plugin');?></a>
		<p><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp_all_export_plugin'); ?></p>
	</div>

	<div class="clear"></div>
</form>

<form name="settings" method="post" action="" class="settings">

    <table class="form-table">
        <tbody>

        <tr>
            <th scope="row"><label><?php esc_html_e('Automatic Scheduling License Key', 'wp_all_export_plugin'); ?></label></th>
            <td>
                <input type="password" class="regular-text" name="scheduling_license"
                       value="<?php if (!empty($post['scheduling_license'])) esc_attr_e(PMXE_Plugin::decode($post['scheduling_license'])); ?>"/>
                <?php if (!empty($post['scheduling_license'])) { ?>

                    <?php if (!empty($post['scheduling_license_status']) && $post['scheduling_license_status'] == 'valid') { ?>
                        <div class="license-status inline updated"><?php esc_html_e('Active', 'wp_all_export_plugin'); ?></div>
                    <?php } else { ?>
                        <input type="submit" class="button-secondary" name="pmxe_scheduling_license_activate"
                               value="<?php esc_html_e('Activate License', 'wp_all_export_plugin'); ?>"/>
                        <div class="license-status inline error"><?php echo esc_html($post['scheduling_license_status']); ?></div>
                    <?php } ?>

                <?php } ?>
                <?php
                $scheduling = \Wpae\Scheduling\Scheduling::create();
                if(!($scheduling->checkLicense())){
                    ?>
                    <p class="description"><?php echo wp_kses_post(__('A license key is required to use Automatic Scheduling. If you have already subscribed, <a href="https://www.wpallimport.com/portal/automatic-scheduling/" target="_blank">click here to access your license key</a>.<br>If you don\'t have a license, <a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=515704&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=automatic-scheduling" target="_blank">click here to subscribe</a>.', 'wp_all_export_plugin')); ?></p>
                    <?php
                }
                ?>

                <p class="submit-buttons">
                    <?php wp_nonce_field('edit-license', '_wpnonce_edit-scheduling-license') ?>
                    <input type="hidden" name="is_scheduling_license_submitted" value="1"/>
                    <input type="submit" class="button-primary" value="Save License"/>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
</form>
<form name="client-mode-settings" method="post" action="" class="client-mode-settings">

    <div>
        <h3>Client Mode</h3>
        <div style="float: left; width: 20%;">
            Roles With Access
        </div>
        <div style="float: left; width: 70%;">

            <?php foreach ($roles as $key => $role) {
                $roleObject = get_role($key);
                ?>
                <input type="checkbox" id="role-<?php echo esc_attr($key); ?>"
                       value="<?php echo esc_attr($key); ?>"
                    <?php if(isset($post['client_mode_roles']) && is_array($post['client_mode_roles']) && in_array($key, $post['client_mode_roles'])) {?> checked="checked" <?php } ?>
                    <?php if($roleObject->has_cap('manage_options')) {?> disabled="disabled" checked="checked" <?php }?>
                       name="client_mode_roles[]"/>
                <label
                        for="role-<?php echo esc_attr($key); ?>"><?php echo esc_html($role['name']); ?> <br/></label>
            <?php } ?>

            <p class="submit-buttons">
                <?php wp_nonce_field('edit-client-mode-settings', '_wpnonce_edit-client_mode_settings') ?>
                <div class="input wp_all_export_save_client_mode_container">
                    <input type="button" class="button-primary wp_all_export_save_client_mode" value="<?php esc_html_e("Save Client Mode Settings", 'wp_all_export_plugin'); ?>"/>
                </div>
            </p>
        </div>
        <div class="clear"></div>
        <div class="wpallexport-free-edition-notice php-client-mode-upgrade" style="margin: 15px 0; padding: 20px; display: none;">
            <a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=2707173&edd_options%5Bprice_id%5D=1&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=client-mode"><?php esc_html_e('Upgrade to the Pro edition of WP All Export to enable Client Mode','wp_all_export_plugin');?></a>
            <p><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp_all_export_plugin'); ?></p>
        </div>
    </div>
</form>

<?php
	$uploads = wp_upload_dir();
	$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
?>
<hr />
<div class="function-editor">
    <h3><?php esc_html_e('Function Editor', 'pmxe_plugin') ?></h3>

    <textarea id="wp_all_export_code" name="wp_all_export_code"><?php echo "<?php\n\n?>";?></textarea>						
    <div class="wpallexport-free-edition-notice php-functions-upgrade" style="margin: 15px 0; padding: 20px; display: none;">
    	<a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=2707173&edd_options%5Bprice_id%5D=1&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=function-editor"><?php esc_html_e('Upgrade to the Pro edition of WP All Export to enable the Function Editor','wp_all_export_plugin');?></a>
    	<p><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp_all_export_plugin'); ?></p>
    </div>

    <div class="input" style="margin-top: 10px;">

    	<div class="input wp_all_export_save_functions_container" style="display:inline-block; margin-right: 20px;">
    		<input type="button" class="button-primary wp_all_export_save_functions" value="<?php esc_html_e("Save Functions", 'wp_all_export_plugin'); ?>"/>
    		<a href="#help" class="wpallexport-help" title="<?php printf(esc_html__("Add functions here for use during your export. You can access this file at %s", "wp_all_export_plugin"), preg_replace("%.*wp-content%", "wp-content", esc_html($functions)));?>" style="top: 0;">?</a>
    		<div class="wp_all_export_functions_preloader"></div>
    	</div>						
        <div class="input wp_all_export_saving_status">

    	</div>

    </div>
</div>
<a href="http://soflyy.com/" target="_blank" class="wpallexport-created-by"><?php esc_html_e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>
