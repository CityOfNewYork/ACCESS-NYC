<div class="wrap">

    <h1><?php _e( 'Import / Export', 'ninja-forms' ); ?></h1>

    <h2 class="nav-tab-wrapper">
        <?php foreach( $tabs as $tab => $name ): ?>
            <?php if( $tab == $active_tab ): ?>
                <span class="nav-tab nav-tab-active"><?php echo $name ?></span>
            <?php else: ?>
                <a href="<?php echo add_query_arg( 'tab', $tab );?>" target="" class="nav-tab "><?php echo $name ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </h2>

    <div id="poststuff">
        <?php do_meta_boxes('nf_import_export_' . $active_tab, 'advanced', NULL ); ?>
    </div>

    <script>
        jQuery(document).ready( function($) {
            // close postboxes that should be closed
            jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');

            // postboxes
            <?php
            global $wp_version;
            if(version_compare($wp_version,"2.7-alpha", "<")){
                echo "add_postbox_toggles('nf_import_export_$active_tab');"; //For WP2.6 and below
            }
            else{
                echo "postboxes.add_postbox_toggles('nf_import_export_$active_tab');"; //For WP2.7 and above
            }
            ?>

        });
    </script>

</div>