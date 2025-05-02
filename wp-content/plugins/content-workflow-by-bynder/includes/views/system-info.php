<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap gc-admin-wrap">
	<h2><?php esc_html_e( 'System Information', 'content-workflow-by-bynder' ); ?></h2>
	<br/>
	<form action="<?php echo esc_url( admin_url( 'admin.php?page=gathercontent-import-support' ) ); ?>" method="post"
		  dir="ltr">
		<textarea readonly="readonly" onclick="this.focus();this.select()" id="system-info-textarea" name="gc-sysinfo"
				  title="<?php esc_html_e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'content-workflow-by-bynder' ); ?>">
### Begin System Info ###

## Please include this information when getting in touch with the Content Workflow (by Bynder) support team ##
<?php do_action( 'cwby_system_info_before' ); ?>

Multisite:                <?php echo esc_html( $this->get( 'multisite' ) ), "\n"; ?>

SITE_URL:                 <?php echo esc_html( $this->get( 'site_url' ) ), "\n"; ?>
HOME_URL:                 <?php echo esc_html( $this->get( 'home_url' ) ), "\n"; ?>

Plugin Version:           <?php echo esc_html( $this->get( 'gc_version' ) ), "\n"; ?>
WordPress Version:        <?php echo esc_html( $this->get( 'wp_version' ) ), "\n"; ?>
Permalink Structure:      <?php echo esc_html( $this->get( 'permalink_structure' ) ), "\n"; ?>
Active Theme:             <?php echo esc_html( $this->get( 'theme' ) ), "\n"; ?>
			<?php if ( $this->get( 'host' ) ) : ?>
				Host:                     <?php echo esc_html( $this->get( 'host' ) ), "\n"; ?>
			<?php endif; ?>

			<?php $this->output( 'browser' ); ?>

PHP Version:              <?php echo esc_html( $this->get( 'php_version' ) ), "\n"; ?>
MySQL Version:            <?php echo esc_html( $this->get( 'mysql_version' ) ), "\n"; ?>
Web Server Info:          <?php echo esc_html( $this->get( 'web_server_info' ) ), "\n"; ?>

WordPress Memory Limit:   <?php echo esc_html( $this->get( 'wordpress_memory_limit' ) ), "\n"; ?>
PHP Safe Mode:            <?php echo esc_html( $this->get( 'php_safe_mode' ) ), "\n"; ?>
PHP Memory Limit:         <?php echo esc_html( $this->get( 'php_memory_limit' ) ), "\n"; ?>
PHP Upload Max Size:      <?php echo esc_html( $this->get( 'php_upload_max_size' ) ), "\n"; ?>
PHP Post Max Size:        <?php echo esc_html( $this->get( 'php_post_max_size' ) ), "\n"; ?>
PHP Upload Max Filesize:  <?php echo esc_html( $this->get( 'php_upload_max_filesize' ) ), "\n"; ?>
PHP Time Limit:           <?php echo esc_html( $this->get( 'php_time_limit' ) ), "\n"; ?>
PHP Max Input Vars:       <?php echo esc_html( $this->get( 'php_max_input_vars' ) ), "\n"; ?>
PHP Arg Separator:        <?php echo esc_html( $this->get( 'php_arg_separator' ) ), "\n"; ?>
PHP Allow URL File Open:  <?php echo esc_html( $this->get( 'php_allow_url_file_open' ) ), "\n"; ?>

WP_DEBUG:                 <?php echo esc_html( $this->get( 'debug' ) ), "\n"; ?>
SCRIPT_DEBUG:             <?php echo esc_html( $this->get( 'script_debug' ) ), "\n"; ?>

WP Table Prefix::         <?php echo esc_html( $this->get( 'pre_length' ) ), "\n"; ?>

Show On Front:            <?php echo esc_html( $this->get( 'show_on_front' ) ), "\n"; ?>
Page On Front:            <?php echo esc_html( $this->get( 'page_on_front' ) ), "\n"; ?>
Page For Posts:           <?php echo esc_html( $this->get( 'page_for_posts' ) ), "\n"; ?>

Session:                  <?php echo esc_html( $this->get( 'session' ) ), "\n"; ?>
Session Name:             <?php echo esc_html( $this->get( 'session_name' ) ), "\n"; ?>
Cookie Path:              <?php echo esc_html( $this->get( 'cookie_path' ) ), "\n"; ?>
Save Path:                <?php echo esc_html( $this->get( 'save_path' ) ), "\n"; ?>
Use Cookies:              <?php echo esc_html( $this->get( 'use_cookies' ) ), "\n"; ?>
Use Only Cookies:         <?php echo esc_html( $this->get( 'use_only_cookies' ) ), "\n"; ?>

DISPLAY ERRORS:           <?php echo esc_html( $this->get( 'display_errors' ) ), "\n"; ?>
FSOCKOPEN:                <?php echo esc_html( $this->get( 'fsockopen' ) ), "\n"; ?>
cURL:                     <?php echo esc_html( $this->get( 'curl' ) ), "\n"; ?>
SOAP Client:              <?php echo esc_html( $this->get( 'soap_client' ) ), "\n"; ?>
SUHOSIN:                  <?php echo esc_html( $this->get( 'suhosin' ) ), "\n"; ?>

ACTIVE PLUGINS:

<?php $this->output( 'active_plugins' ); ?>
			<?php if ( $this->get( 'network_active_plugins' ) ) : ?>

				NETWORK ACTIVE PLUGINS:

				<?php $this->output( 'network_active_plugins' ); ?>
			<?php endif; ?>

Plugin Options:           <?php echo esc_html( $this->get( 'gc_options' ) ), "\n"; ?>
			<?php do_action( 'cwby_system_info_after' ); ?>

### End System Info ###</textarea>
		<p><strong><?php esc_html_e( 'For more information:', 'content-workflow-by-bynder' ); ?></strong></p>
		<p><a href="https://gathercontent.com/support/wordpress-integration/"
			  target="_blank"><?php esc_html_e( 'Support for Content Workflow WordPress Integration', 'content-workflow-by-bynder' ); ?></a>
		</p>
		<p><a href="https://wordpress.org/support/plugin/gathercontent-import"
			  target="_blank"><?php esc_html_e( 'WordPress Plugin Support Forums', 'content-workflow-by-bynder' ); ?></a>
		</p>

		<p>
			<?php
			echo '<strong>' .
				 esc_html__( 'This information contains potentially senstive data.', 'content-workflow-by-bynder' ) .
				 '</strong><br>' .
				 esc_html__( 'Please be careful with where you post it. Do not post it in the WordPress support forums.', 'content-workflow-by-bynder' );
			?>
		</p>
		<p class="submit">
			<?php wp_nonce_field( 'gc-download-sysinfo-nonce', 'gc-download-sysinfo-nonce' ); ?>
			<?php submit_button( 'Download System Info File', 'primary', 'gc-download-sysinfo', false ); ?>
		</p>
	</form>
</div>
