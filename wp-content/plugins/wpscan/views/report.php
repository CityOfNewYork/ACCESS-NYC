<?php
  # Exit if accessed directly.
  defined( 'ABSPATH' ) || exit;

  $ignored = get_option($this->parent->OPT_IGNORE_ITEMS, []);
  $ignored_msg = __('<span class="wpscan-ignored">Ignored from the settings</span>', 'wpscan');
?>

<div class="wrap">
  <?php echo file_get_contents($this->parent->plugin_dir. 'assets/svg/logo.svg'); ?>
  
  <hr class="wp-header-end">

  <?php if( $this->parent->is_interval_scanning_disabled() ) : ?>
    <div class="notice notice-error">
      <p><?php _e('Automated scanning is currently disabled using the <code>WPSCAN_DISABLE_SCANNING_INTERVAL</code> constant. You can still run scans manually.', 'wpscan') ?></p>
    </div>
  <?php endif; ?>

  <?php if ( get_transient( $this->parent->WPSCAN_TRANSIENT_CRON ) ) : ?>
    <div class="notice notice-info">
      <p><?php _e( 'The task is running in the background. This page will be reloaded once finished to display the results.', 'wpscan' ) ?></p>
    </div>
  <?php endif; ?>

  <?php settings_errors(); ?>

  <div id="poststuff">

    <div id="post-body" class="metabox-holder columns-2">

      <div id="postbox-body" class="metabox-holder columns-2">

        <div id="post-body-content">
        
          <div class="wpscan-report-section">
            <h3><?php _e( 'WordPress', 'wpscan' ) ?></h3>

            <table class="wp-list-table widefat striped plugins">
            <thead>
              <tr>
                <td scope="col" class="manage-column check-column">&nbsp;</td>
                <th scope="col" class="manage-column column-name column-primary" width="250"><?php _e( 'Name', 'wpscan' ) ?></th>
                <th scope="col" class="manage-column column-description"><?php _e( 'Vulnerabilities', 'wpscan' ) ?></th>
              </tr>
            </thead>
            <tbody id="report-wordpress">
              <tr>
                <th scope="row" class="check-column" style="text-align: center">
                  <?php echo $this->get_status( 'wordpress', get_bloginfo( 'version' ) ) ?></th>
                <td class="plugin-title column-primary">
                  <strong>WordPress</strong> 
                  <span class='item-version'>
                    <?php echo sprintf( __( 'Version <span>%s</span>', 'wpscan' ), get_bloginfo( 'version' ) ) ?>
                  </span>
                </td>
                <td class="vulnerabilities">
                  <?php 
                    if ( !isset($ignored['wordpress']) ) {
                      $this->list_api_vulnerabilities( 'wordpress', get_bloginfo( 'version' ) );
                    } else {
                      echo $ignored_msg;
                    }
                  ?>
                </td>
              </tr>
            </tbody>
            </table>        
          </div>

          <div class="wpscan-report-section">
            <h3><?php _e( 'Plugins', 'wpscan' ) ?></h3>

            <table class="wp-list-table widefat striped plugins">
              <thead>
                <tr>
                  <td scope="col" class="manage-column check-column">&nbsp;</td>
                  <th scope="col" class="manage-column column-name column-primary" width="250"><?php _e( 'Name', 'wpscan' ) ?></th>
                  <th scope="col" class="manage-column column-description"><?php _e( 'Vulnerabilities', 'wpscan' ) ?></th>
                </tr>
              </thead>
              <tbody id="report-plugins">
                <?php 
                  foreach ( get_plugins() as $name => $details ) {
                    $slug = $this->parent->get_plugin_slug( $name, $details );
                    $is_closed = $this->is_item_closed('plugins', $slug);
                ?>
                <tr>
                  <th scope="row" class="check-column" style="text-align: center">
                    <?php echo $this->get_status( 'plugins', $slug ) ?>
                  </th>
                  
                  <td class="plugin-title column-primary">
                    <strong><?php echo esc_html($details['Name']) ?></strong>
                    <span class='item-version'>
                      <?php echo sprintf( __( 'Version <span>%s</span>', 'wpscan' ), esc_html($details['Version']) ) ?>
                    </span>
                    <?php if ($is_closed) { ?>
                      <span class='item-closed'>Plugin Closed</span>
                    <?php } ?>
                  </td>
                  <td class="vulnerabilities">
                    <?php 
                      if ( ! isset($ignored['plugins'][$slug]) ){
                        $this->list_api_vulnerabilities( 'plugins', $slug );
                      }
                      else {
                        echo $ignored_msg;
                      }
                    ?>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>

          </div>
    
          <div class="wpscan-report-section">
            <h3><?php _e( 'Themes', 'wpscan' ) ?></h3>

            <table class="wp-list-table widefat striped plugins">
              <thead>
                <tr>
                  <td scope="col" class="manage-column check-column">&nbsp;</td>
                  <th scope="col" class="manage-column column-name column-primary" width="250"><?php _e( 'Name', 'wpscan' ) ?></th>
                  <th scope="col" class="manage-column column-description"><?php _e( 'Vulnerabilities', 'wpscan' ) ?></th>
                </tr>
              </thead>
              <tbody id="report-themes">
                <?php foreach ( wp_get_themes() as $name => $details ):
                  $slug = $this->parent->get_theme_slug( $name, $details );
                  $is_closed = $this->is_item_closed('themes', $slug);
                ?>
                <tr>
                  <th scope="row" class="check-column" style="text-align: center">
                    <?php echo $this->get_status( 'themes', $slug ) ?></th>
                  <td class="plugin-title column-primary">
                    <strong><?php echo esc_html($details['Name']) ?></strong>
                    <span class='item-version'>
                      <?php echo sprintf( __( 'Version <span>%s</span>', 'wpscan' ), esc_html($details['Version']) ) ?>
                    </span>
                    <?php if ($is_closed) { ?>
                      <span class='item-closed'>Theme Closed</span>
                    <?php } ?>
                  </td>
                  <td class="vulnerabilities">
                    <?php 
                      if ( ! isset($ignored['themes'][$slug]) )
                        $this->list_api_vulnerabilities( 'themes', $slug );
                      else {
                        echo $ignored_msg;
                      }
                    ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

          </div>

          <div class="wpscan-report-section security-checks">
            <h3><?php _e('Security Checks', 'wpscan') ?></h3>

            <table class="wp-list-table widefat striped plugins">
                <thead>
                    <tr>
                        <td scope="col" class="manage-column check-column"></td>
                        <th scope="col" class="manage-column column-name column-primary" width="250"><?php _e('Name', 'wpscan') ?></th>
                        <th scope="col" class="manage-column column-description"><?php _e('Result', 'wpscan') ?></th>
                        <th scope="col" class="manage-column column-description"><?php _e('Actions', 'wpscan') ?></th>
                    </tr>
                </thead>
                <tbody id="report-themes">
                    <?php foreach ( $this->parent->classes['checks/system']->checks as $id => $data ) : ?>
                        <tr>
                            <th scope="row" class="check-column" style="text-align: center">
                              <?php echo $this->get_status('security-checks', $id) ?></th>
                            </th>
                            <td class="plugin-title column-primary">
                                <strong title="<?php echo esc_attr($data['instance']->description()) ?>">
                                  <?php echo esc_html($data['instance']->title()) ?>
                                </strong>
                            </td>
                            <td class="vulnerabilities">
                              <?php $this->parent->classes['checks/system']->list_check_vulnerabilities( $data['instance'] ) ?>
                            </td>
                            <td class="security-check-actions">
                                <?php $this->parent->classes['checks/system']->list_actions($data['instance']) ?>                              
                                <span class="spinner"></span>
                            </td>
                        </tr
                    <?php endforeach; ?>
                </tbody>
            </table>
          </div>
        
          <?php if ( get_option( $this->parent->OPT_API_TOKEN ) ) { ?>
            <a href="#" class='button button-secondary download-report'><?php _e( 'Download as PDF', 'wpscan' ) ?></a>
          <?php } ?>
        
        </div>

      </div>

      <div id="postbox-container-1" class="postbox-container">
        <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
        <?php do_meta_boxes( 'wpscan', 'side', null ); ?>
      </div>

    </div>

    <br class="clear">

  </div>

</div>