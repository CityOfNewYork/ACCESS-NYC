<?php
// File Security Check
defined( 'ABSPATH' ) or die( "No script kiddies please!" );
?>

<div class="wrap">

  <h1><img src="<?php echo self::$plugin_url . 'assets/logo.svg' ?>" alt="WPScan"></h1>

  <?php if ( get_transient( self::WPSCAN_TRANSIENT_CRON ) ) : ?>
  <div class="notice notice-info">
    <p><?php _e( 'The task is running in the background. This page will be reloaded once finished to display the results.', 'wpscan' ) ?></p>
  </div>
  <?php endif; ?>

  <?php settings_errors(); ?>

  <div id="poststuff">

    <div id="post-body" class="metabox-holder columns-2">

      <div id="postbox-body" class="postbox-container">

        <h3>WordPress</h3>

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
            <th scope="row" class="check-column" style="text-align: center"><?php echo self::get_status( 'wordpress', get_bloginfo( 'version' ) ) ?></span></th>
            <td class="plugin-title column-primary">
              <strong>WordPress</strong> <?php echo sprintf( __( 'Version %s', 'wpscan' ), get_bloginfo( 'version' ) ) ?>
            </td>
            <td><?php self::list_vulnerabilities( 'wordpress', get_bloginfo( 'version' ) ) ?></td>
          </tr>
        </tbody>
        </table>

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
          <?php foreach ( get_plugins() as $name => $details ) : $name = self::sanitize_plugin_name( $name, $details ); ?>
          <tr>
            <th scope="row" class="check-column" style="text-align: center"><?php echo self::get_status( 'plugins', $name ) ?></span></th>
            <td class="plugin-title column-primary">
              <strong><?php echo esc_html($details['Name']) ?></strong> <?php echo sprintf( __( 'Version %s', 'wpscan' ), esc_html($details['Version']) ) ?>
            </td>
            <td><?php self::list_vulnerabilities( 'plugins', $name ) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        </table>

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
          <?php foreach ( wp_get_themes() as $name => $details ) : $name = self::sanitize_theme_name( $name, $details ); ?>
          <tr>
            <th scope="row" class="check-column" style="text-align: center"><?php echo self::get_status( 'themes', $name ) ?></span></th>
            <td class="plugin-title column-primary">
              <strong><?php echo esc_html($details['Name']) ?></strong> <?php echo sprintf( __( 'Version %s', 'wpscan' ), esc_html($details['Version']) ) ?>
            </td>
            <td><?php self::list_vulnerabilities( 'themes', $name ) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        </table>

      </div>

      <div id="postbox-container-1" class="postbox-container">

        <?php do_meta_boxes( 'wpscan', 'side', null ); ?>

      </div>

    </div>

    <br class="clear">

  </div>

</div>