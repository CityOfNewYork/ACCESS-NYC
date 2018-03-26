<?php

// Translations
$i18n = array(
    'All post types' => __( 'All post types', 'fwp' ),
    'Indexing complete' => __( 'Indexing complete', 'fwp' ),
    'Indexing' => __( 'Indexing', 'fwp' ),
    'Saving' => __( 'Saving', 'fwp' ),
    'Loading' => __( 'Loading', 'fwp' ),
    'Importing' => __( 'Importing', 'fwp' ),
    'Activating' => __( 'Activating', 'fwp' ),
    'Are you sure?' => __( 'Are you sure?', 'fwp' ),
    'Select some items' => __( 'Select some items', 'fwp' ),
);

// An array of facet type objects
$facet_types = FWP()->helper->facet_types;

// Clone facet settings HTML
$facet_clone = array();
foreach ( $facet_types as $name => $class ) {
    $facet_clone[ $name ] = __( 'This facet type has no additional settings.', 'fwp' );
    if ( method_exists( $class, 'settings_html' ) ) {
        ob_start();
        $class->settings_html();
        $facet_clone[ $name ] = ob_get_clean();
    }
}

// Settings
$settings_admin = new FacetWP_Settings_Admin();
$settings_array = $settings_admin->get_settings();
$builder = $settings_admin->get_query_builder_choices();
$sources = FWP()->helper->get_data_sources();

?>

<script src="<?php echo FACETWP_URL; ?>/assets/js/src/event-manager.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/js/src/query-builder.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/fSelect/fSelect.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<?php
foreach ( $facet_types as $class ) {
    $class->admin_scripts();
}
?>
<script src="<?php echo FACETWP_URL; ?>/assets/js/src/admin.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script>
FWP.i18n = <?php echo json_encode( $i18n ); ?>;
FWP.nonce = '<?php echo wp_create_nonce( 'fwp_admin_nonce' ); ?>';
FWP.settings = <?php echo json_encode( FWP()->helper->settings ); ?>;
FWP.clone = <?php echo json_encode( $facet_clone ); ?>;
FWP.builder = <?php echo json_encode( $builder ); ?>;
</script>
<link href="<?php echo FACETWP_URL; ?>/assets/css/admin.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">
<link href="<?php echo FACETWP_URL; ?>/assets/vendor/fSelect/fSelect.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">

<div class="facetwp-header">
    <span class="facetwp-logo" title="FacetWP">&nbsp;</span>
    <span class="facetwp-version">v<?php echo FACETWP_VERSION; ?></span>

    <span class="facetwp-header-nav">
        <a class="facetwp-tab" rel="basics"><?php _e( 'Basics', 'fwp' ); ?></a>
        <a class="facetwp-tab" rel="settings"><?php _e( 'Settings', 'fwp' ); ?></a>
        <a class="facetwp-tab" rel="support"><?php _e( 'Support', 'fwp' ); ?></a>
    </span>

    <span class="facetwp-actions">
        <span class="facetwp-response"></span>
        <a class="button facetwp-rebuild"><?php _e( 'Re-index', 'fwp' ); ?></a>
        <a class="button-primary facetwp-save"><?php _e( 'Save Changes', 'fwp' ); ?></a>
    </span>
</div>

<div class="wrap">

    <div class="facetwp-loading"></div>

    <!-- Basics tab -->

    <div class="facetwp-region facetwp-region-basics">
        <div class="facetwp-subnav">
            <span class="search-wrap">
                <input type="text" class="facetwp-search" placeholder="Search for a facet or template" />
            </span>
            <span class="btn-wrap hidden">
                <a class="button facetwp-back"><?php _e( 'Back', 'fwp' ); ?></a>
            </span>
        </div>

        <div class="facetwp-grid">
            <div class="facetwp-col content-facets">
                <h3>
                    Facets
                    <span class="facetwp-btn facetwp-add">Add new</span>
                    <a class="icon-question" href="https://facetwp.com/documentation/facet-configuration/" target="_blank">?</a>
                </h3>
                <ul class="facetwp-cards"></ul>
            </div>

            <div class="facetwp-col content-templates">
                <h3>
                    Templates
                    <span class="facetwp-btn facetwp-add">Add new</span>
                    <a class="icon-question" href="https://facetwp.com/documentation/template-configuration/" target="_blank">?</a>
                </h3>
                <ul class="facetwp-cards"></ul>
            </div>
        </div>

        <div class="facetwp-content"></div>
    </div>

    <!-- Settings tab -->

    <div class="facetwp-region facetwp-region-settings">
        <div class="facetwp-subnav">
            <?php foreach ( $settings_array as $key => $tab ) : ?>
            <a data-tab="<?php echo $key; ?>"><?php echo $tab['label']; ?></a>
            <?php endforeach; ?>
        </div>

        <?php foreach ( $settings_array as $key => $tab ) : ?>
        <div class="facetwp-settings-section" data-tab="<?php echo $key; ?>">
            <?php foreach ( $tab['fields'] as $field_data ) : ?>
            <table>
                <tr>
                    <td>
                        <?php echo $field_data['label']; ?>
                        <?php if ( isset( $field_data['notes'] ) ) : ?>
                        <div class="facetwp-tooltip">
                            <span class="icon-question">?</span>
                            <div class="facetwp-tooltip-content"><?php echo $field_data['notes']; ?></div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $field_data['html']; ?></td>
                </tr>
            </table>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Support tab -->

    <div class="facetwp-region facetwp-region-support">
        <?php include( FACETWP_DIR . '/templates/page-support.php' ); ?>
    </div>

    <!-- Hidden: clone settings -->

    <div class="hidden clone-facet">
        <div class="facetwp-row">
            <div class="table-row code-unlock">
                This facet is locked to prevent changes. <button class="unlock">Unlock now</button>
            </div>
            <table>
                <tr>
                    <td><?php _e( 'Label', 'fwp' ); ?>:</td>
                    <td>
                        <input type="text" class="facet-label" value="New facet" />
                        &nbsp; &nbsp;
                        <?php _e( 'Name', 'fwp' ); ?>: <span class="facet-name" contentEditable="true">new_facet</span>
                    </td>
                </tr>
                <tr>
                    <td><?php _e( 'Facet type', 'fwp' ); ?>:</td>
                    <td>
                        <select class="facet-type">
                            <?php foreach ( $facet_types as $name => $class ) : ?>
                            <option value="<?php echo $name; ?>"><?php echo $class->label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        &nbsp; &nbsp;
                        <span class="facetwp-btn copy-shortcode">Copy shortcode</span>
                    </td>
                </tr>
                <tr class="facetwp-show name-source">
                    <td>
                        <?php _e( 'Data source', 'fwp' ); ?>:
                    </td>
                    <td>
                        <select class="facet-source">
                            <?php foreach ( $sources as $group ) : ?>
                            <optgroup label="<?php echo $group['label']; ?>">
                                <?php foreach ( $group['choices'] as $val => $label ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <hr />
            <table class="facet-fields"></table>
        </div>
    </div>

    <div class="hidden clone-template">
        <div class="facetwp-row">
            <div class="table-row code-unlock">
                This template is locked to prevent changes. <button class="unlock">Unlock now</button>
            </div>
            <div class="table-row">
                <input type="text" class="template-label" value="New template" />
                &nbsp; &nbsp;
                <?php _e( 'Name', 'fwp' ); ?>: <span class="template-name" contentEditable="true">new_template</span>
            </div>
            <div class="table-row">
                <div class="side-link open-builder"><?php _e( 'Open query builder', 'fwp' ); ?></div>
                <div class="row-label"><?php _e( 'Query Arguments', 'fwp' ); ?></div>
                <textarea class="template-query"></textarea>
            </div>
            <div class="table-row">
                <div class="side-link"><a href="https://facetwp.com/documentation/template-configuration/#display-code" target="_blank"><?php _e( 'What goes here?', 'fwp' ); ?></a></div>
                <div class="row-label"><?php _e( 'Display Code', 'fwp' ); ?></div>
                <textarea class="template-template"></textarea>
            </div>
        </div>
    </div>

    <!-- Copy to clipboard -->

    <input class="hidden facetwp-clipboard" value="" />
</div>

<!-- Modal window -->

<div class="media-modal">
    <button class="button-link media-modal-close"><span class="media-modal-icon"></span></button>
    <div class="media-modal-content">
        <div class="media-frame">
            <div class="media-frame-title">
                <h1><?php _e( 'Query Builder', 'fwp' ); ?></h1>
            </div>
            <div class="media-frame-router">
                <div class="media-router">
                    <?php _e( 'Which posts would you like to use for the listing?', 'fwp' ); ?>
                </div>
            </div>
            <div class="media-frame-content">
                <div class="modal-content-wrap">
                    <div class="facetwp-modal-grid">
                        <div class="qb-area"></div>
                        <div class="qb-area-results">
                            <textarea class="qb-results" readonly></textarea>
                            <button class="button qb-send"><?php _e( 'Send to editor', 'fwp' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="media-modal-backdrop"></div>
