<div class="wrap gc-admin-wrap">
	<h2><?php _e( 'System Information', 'gathercontent-import' ); ?></h2>
	<style type="text/css" media="screen">
		#system-info-textarea {
			background: none;
			font-family: Menlo, Monaco, monospace;
			display: block;
			overflow: auto;
			white-space: pre;
			width: 800px;
			height: 400px;
			min-height: 400px;
			margin-bottom: 1.5em;
		}
	</style>
	<br/>
	<form action="<?php echo esc_url( admin_url( 'admin.php?page=gathercontent-import-support' ) ); ?>" method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus();this.select()" id="system-info-textarea" name="gc-sysinfo" title="<?php _e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'gathercontent-import' ); ?>">
### Begin System Info ###

## Please include this information when getting in touch with the GatherContent support team ##
<?php do_action( 'gc_system_info_before' ); ?>

Multisite:                <?php echo $this->get( 'multisite' ), "\n" ?>

SITE_URL:                 <?php echo $this->get( 'site_url' ), "\n" ?>
HOME_URL:                 <?php echo $this->get( 'home_url' ), "\n" ?>

Plugin Version:           <?php echo $this->get( 'gc_version' ), "\n" ?>
WordPress Version:        <?php echo $this->get( 'wp_version' ), "\n" ?>
Permalink Structure:      <?php echo $this->get( 'permalink_structure' ), "\n" ?>
Active Theme:             <?php echo $this->get( 'theme' ), "\n" ?>
<?php if ( $this->get( 'host' ) ) : ?>
Host:                     <?php echo $this->get( 'host' ), "\n" ?>
<?php endif; ?>

<?php $this->output( 'browser' ); ?>

PHP Version:              <?php echo $this->get( 'php_version' ), "\n" ?>
MySQL Version:            <?php echo $this->get( 'mysql_version' ), "\n" ?>
Web Server Info:          <?php echo $this->get( 'web_server_info' ), "\n" ?>

WordPress Memory Limit:   <?php echo $this->get( 'wordpress_memory_limit' ), "\n" ?>
PHP Safe Mode:            <?php echo $this->get( 'php_safe_mode' ), "\n" ?>
PHP Memory Limit:         <?php echo $this->get( 'php_memory_limit' ), "\n" ?>
PHP Upload Max Size:      <?php echo $this->get( 'php_upload_max_size' ), "\n" ?>
PHP Post Max Size:        <?php echo $this->get( 'php_post_max_size' ), "\n" ?>
PHP Upload Max Filesize:  <?php echo $this->get( 'php_upload_max_filesize' ), "\n" ?>
PHP Time Limit:           <?php echo $this->get( 'php_time_limit' ), "\n" ?>
PHP Max Input Vars:       <?php echo $this->get( 'php_max_input_vars' ), "\n" ?>
PHP Arg Separator:        <?php echo $this->get( 'php_arg_separator' ), "\n" ?>
PHP Allow URL File Open:  <?php echo $this->get( 'php_allow_url_file_open' ), "\n" ?>

WP_DEBUG:                 <?php echo $this->get( 'debug' ), "\n" ?>
SCRIPT_DEBUG:             <?php echo $this->get( 'script_debug' ), "\n" ?>

WP Table Prefix::         <?php echo $this->get( 'pre_length' ), "\n" ?>

Show On Front:            <?php echo $this->get( 'show_on_front' ), "\n" ?>
Page On Front:            <?php echo $this->get( 'page_on_front' ), "\n" ?>
Page For Posts:           <?php echo $this->get( 'page_for_posts' ), "\n" ?>

WP Remote Post:           <?php echo $this->get( 'wp_remote_post' ), "\n" ?>

Session:                  <?php echo $this->get( 'session' ), "\n" ?>
Session Name:             <?php echo $this->get( 'session_name' ), "\n"; ?>
Cookie Path:              <?php echo $this->get( 'cookie_path' ), "\n"; ?>
Save Path:                <?php echo $this->get( 'save_path' ), "\n"; ?>
Use Cookies:              <?php echo $this->get( 'use_cookies' ), "\n"; ?>
Use Only Cookies:         <?php echo $this->get( 'use_only_cookies' ), "\n"; ?>

DISPLAY ERRORS:           <?php echo $this->get( 'display_errors' ), "\n"; ?>
FSOCKOPEN:                <?php echo $this->get( 'fsockopen' ), "\n"; ?>
cURL:                     <?php echo $this->get( 'curl' ), "\n"; ?>
SOAP Client:              <?php echo $this->get( 'soap_client' ), "\n"; ?>
SUHOSIN:                  <?php echo $this->get( 'suhosin' ), "\n"; ?>

ACTIVE PLUGINS:

<?php $this->output( 'active_plugins' ); ?>
<?php if ( $this->get( 'network_active_plugins' ) ) :?>

NETWORK ACTIVE PLUGINS:

<?php $this->output( 'network_active_plugins' ); ?>
<?php endif; ?>

Plugin Options:           <?php echo $this->get( 'gc_options' ), "\n" ?>
<?php do_action( 'gc_system_info_after' ); ?>

### End System Info ###</textarea>
		<p><strong><?php  _e( 'For more information:', 'gathercontent-import' ); ?></strong></p>
		<p><a href="https://gathercontent.com/support/wordpress-integration/" target="_blank"><?php _e( 'Support for GatherContent WordPress Integration' ); ?></a></p>
		<p><a href="https://wordpress.org/support/plugin/gathercontent-import" target="_blank"><?php _e( 'WordPress Plugin Support Forums' ); ?></a></p>

		<p><?php _e( '<strong>This information contains potentially senstive data.</strong><br>Please be careful with where you post it. Do not post it in the WordPress support forums.', 'gathercontent-import' ); ?></p>
		<p class="submit">
			<?php wp_nonce_field( 'gc-download-sysinfo-nonce', 'gc-download-sysinfo-nonce' ); ?>
			<?php submit_button( 'Download System Info File', 'primary', 'gc-download-sysinfo', false ); ?>
		</p>
	</form>
	<script>
		jQuery( function( $ ) {
			$( document.getElementById( 'system-info-textarea' ) ).height( $( window ).height() * .7 );
		});
	</script>
</div>
