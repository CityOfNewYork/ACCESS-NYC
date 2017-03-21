<?php

global $wpdb;

// Translations
$i18n = array(
    'All post types' => __( 'All post types', 'fwp' ),
    'Indexing complete' => __( 'Indexing complete', 'fwp' ),
    'Indexing' => __( 'Indexing', 'fwp' ),
    'Saving' => __( 'Saving', 'fwp' ),
    'Importing' => __( 'Importing', 'fwp' ),
    'Activating' => __( 'Activating', 'fwp' ),
    'Are you sure?' => __( 'Are you sure?', 'fwp' ),
);

// An array of facet type objects
$facet_types = FWP()->helper->facet_types;

// Get taxonomy list
$taxonomies = get_taxonomies( array(), 'object' );

// Get post types & taxonomies for the Query Builder
$builder_taxonomies = array();
foreach ( $taxonomies as $tax ) {
    $builder_taxonomies[ $tax->name ] = $tax->labels->singular_name;
}

$builder_post_types = array();
$post_types = get_post_types( array( 'public' => true ), 'objects' );
foreach ( $post_types as $type ) {
    $builder_post_types[ $type->name ] = $type->labels->name;
}

// Activation status
$message = __( 'Not yet activated', 'fwp' );
$activation = get_option( 'facetwp_activation' );
if ( ! empty( $activation ) ) {
    $activation = json_decode( $activation );
    if ( 'success' == $activation->status ) {
        $message = __( 'License active', 'fwp' );
        $message .= ' (' . __( 'expires', 'fwp' ) . ' ' . date( 'M j, Y', strtotime( $activation->expiration ) ) . ')';
    }
    else {
        $message = $activation->message;
    }
}

// Export feature
$export = array();
$settings = FWP()->helper->settings;

foreach ( $settings['facets'] as $facet ) {
    $export['facet-' . $facet['name']] = 'Facet - ' . $facet['label'];
}

foreach ( $settings['templates'] as $template ) {
    $export['template-' . $template['name']] = 'Template - '. $template['label'];
}

// Data sources
$sources = FWP()->helper->get_data_sources();

?>

<script src="<?php echo FACETWP_URL; ?>/assets/js/event-manager.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/js/src/query-builder.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/js/fSelect/fSelect.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<?php
foreach ( $facet_types as $class ) {
    $class->admin_scripts();
}
?>
<script src="<?php echo FACETWP_URL; ?>/assets/js/admin.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script>
FWP.i18n = <?php echo json_encode( $i18n ); ?>;

FWP.builder = {
    post_types: <?php echo json_encode( $builder_post_types ); ?>,
    taxonomies: <?php echo json_encode( $builder_taxonomies ); ?>
};
</script>
<link href="<?php echo FACETWP_URL; ?>/assets/css/admin.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">
<link href="<?php echo FACETWP_URL; ?>/assets/js/fSelect/fSelect.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">

<div class="facetwp-header">
    <span class="facetwp-logo" title="FacetWP">&nbsp;</span>
    <span class="facetwp-header-nav">
        <a class="facetwp-tab" rel="welcome"><?php _e( 'Welcome', 'fwp' ); ?></a>
        <a class="facetwp-tab" rel="facets"><?php _e( 'Facets', 'fwp' ); ?></a>
        <a class="facetwp-tab" rel="templates"><?php _e( 'Templates', 'fwp' ); ?></a>
        <a class="facetwp-tab" rel="settings"><?php _e( 'Settings', 'fwp' ); ?></a>
        <a class="facetwp-tab" rel="support"><?php _e( 'Support', 'fwp' ); ?></a>
    </span>
</div>

<div class="wrap">

    <div class="facetwp-response"></div>
    <div class="facetwp-loading"></div>

    <!-- Welcome tab -->

    <div class="facetwp-region facetwp-region-welcome about-wrap">
        <h1><?php _e( 'Welcome to FacetWP', 'fwp' ); ?> <span class="version"><?php echo FACETWP_VERSION; ?></span></h1>
        <div class="about-text">Thank you for choosing FacetWP. Below is a quick introduction to the plugin's key components - Facets and Templates.</div>
        <div class="welcome-box-wrap">
            <div class="welcome-box">
                <h2><?php _e( 'Facets', 'fwp' ); ?></h2>
                <p>Facets are interactive elements used to narrow lists of content.</p>
                <a class="button" href="https://facetwp.com/documentation/facet-configuration/" target="_blank">Learn more</a>
            </div>
            <div class="welcome-box">
                <h2><?php _e( 'Templates', 'fwp' ); ?></h2>
                <p>In order for facets to appear, FacetWP needs to know <strong>which posts to filter upon</strong>. There are two ways to do it:</p>
                <p><strong>(Option 1) Add a CSS class to your theme file</strong></p>
                <p>For Search and Archive pages, simply add the CSS class "<strong>facetwp-template</strong>" to an HTML element surrounding the <a href="http://www.elegantthemes.com/blog/tips-tricks/the-wordpress-loop-explained-for-beginners" target="_blank">WordPress Loop</a>. FacetWP will attempt to auto-detect the listing.</p>
                <p><strong>(Option 2) Create a FacetWP template</strong></p>
                <p>Within the Templates tab, click "Add new". This method generates a shortcode, which can be pasted into the WYSIWYG editor, a text widget, or into a theme file (see the <code>facetwp_display</code> function).</p>
                <a class="button" href="https://facetwp.com/documentation/template-configuration/" target="_blank">Learn more</a>
            </div>
        </div>
    </div>

    <!-- Facets tab -->

    <div class="facetwp-region facetwp-region-facets">
        <div class="flexbox">
            <div class="left-side">
                <span class="btn-wrap">
                    <a class="button facetwp-add"><?php _e( 'Add New', 'fwp' ); ?></a>
                </span>
                <span class="btn-wrap hidden">
                    <a class="button facetwp-back"><?php _e( 'Back', 'fwp' ); ?></a>
                </span>
            </div>
            <div class="right-side">
                <a class="button facetwp-rebuild"><?php _e( 'Re-index', 'fwp' ); ?></a>
                <a class="button-primary facetwp-save"><?php _e( 'Save Changes', 'fwp' ); ?></a>
            </div>
        </div>

        <div class="facetwp-content-wrap">
            <ul class="facetwp-cards"></ul>
            <div class="facetwp-content"></div>
        </div>
    </div>

    <!-- Templates tab -->

    <div class="facetwp-region facetwp-region-templates">
        <div class="flexbox">
            <div class="left-side">
                <span class="btn-wrap">
                    <a class="button facetwp-add"><?php _e( 'Add New', 'fwp' ); ?></a>
                </span>
                <span class="btn-wrap hidden">
                    <a class="button facetwp-back"><?php _e( 'Back', 'fwp' ); ?></a>
                </span>
            </div>
            <div class="right-side">
                <a class="button-primary facetwp-save"><?php _e( 'Save Changes', 'fwp' ); ?></a>
            </div>
        </div>

        <div class="facetwp-content-wrap">
            <ul class="facetwp-cards"></ul>
            <div class="facetwp-content"></div>
        </div>
    </div>

    <!-- Settings tab -->

    <div class="facetwp-region facetwp-region-settings">
        <div class="flexbox">
            <div class="left-side">
            </div>
            <div class="right-side">
                <a class="button-primary facetwp-save"><?php _e( 'Save Changes', 'fwp' ); ?></a>
            </div>
        </div>

        <div class="facetwp-content-wrap">
            <table>
                <tr>
                    <td style="width:175px"><?php _e( 'License Key', 'fwp' ); ?></td>
                    <td>
                        <input type="text" class="facetwp-license" style="width:280px" value="<?php echo get_option( 'facetwp_license' ); ?>" />
                        <input type="button" class="button facetwp-activate" value="<?php _e( 'Activate', 'fwp' ); ?>" />
                        <div class="facetwp-activation-status field-notes"><?php echo $message; ?></div>
                    </td>
                </tr>
            </table>

            <!-- General settings -->

            <table>
                <tr>
                    <td style="width:175px; vertical-align:top">
                        <?php _e( 'Permalink Type', 'fwp' ); ?>
                        <div class="facetwp-tooltip">
                            <span class="icon-question">?</span>
                            <div class="facetwp-tooltip-content"><?php _e( 'How should permalinks be constructed?', 'fwp' ); ?></div>
                        </div>
                    </td>
                    <td>
                        <select class="facetwp-setting" data-name="permalink_type">
                            <option value="get"><?php _e( 'GET variables', 'fwp' ); ?></option>
                            <option value="hash"><?php _e( 'URL Hash', 'fwp' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="width:175px; vertical-align:top">
                        <?php _e( 'Separators', 'fwp' ); ?>
                    </td>
                    <td>
                        <?php _e( 'Thousands', 'fwp' ); ?>
                        <input type="text" style="width:50px" class="facetwp-setting" data-name="thousands_separator" />
                        <?php _e( 'Decimal', 'fwp' ); ?>
                        <input type="text" style="width:50px" class="facetwp-setting" data-name="decimal_separator" />
                    </td>
                </tr>
            </table>

            <!-- Migration -->

            <table>
                <tr>
                    <td style="width:175px; vertical-align:top">
                        <?php _e( 'Export', 'fwp' ); ?>
                    </td>
                    <td valign="top" style="width:260px">
                        <select class="export-items" multiple="multiple" style="width:250px; height:100px">
                            <?php foreach ( $export as $val => $label ) : ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="margin-top:5px"><a class="button export-submit"><?php _e( 'Export', 'fwp' ); ?></a></div>
                    </td>
                    <td valign="top">
                        <textarea class="export-code" placeholder="Loading..."></textarea>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="width:175px; vertical-align:top">
                        <?php _e( 'Import', 'fwp' ); ?>
                    </td>
                    <td>
                        <div><textarea class="import-code" placeholder="<?php _e( 'Paste the import code here', 'fwp' ); ?>"></textarea></div>
                        <div><input type="checkbox" class="import-overwrite" /> <?php _e( 'Overwrite existing items?', 'fwp' ); ?></div>
                        <div style="margin-top:5px"><a class="button import-submit"><?php _e( 'Import', 'fwp' ); ?></a></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Support tab -->

    <div class="facetwp-region facetwp-region-support">
        <div class="facetwp-content-wrap">
            <?php include( FACETWP_DIR . '/templates/page-support.php' ); ?>
        </div>
    </div>

    <!-- Hidden: clone settings -->

<?php
$settings = array();
foreach ( $facet_types as $name => $class ) {
    $settings[ $name ] = __( 'This facet type has no additional settings.', 'fwp' );
    if ( method_exists( $class, 'settings_html' ) ) {
        ob_start();
        $class->settings_html();
        $settings[ $name ] = ob_get_clean();
    }
}
?>

<script>
var FWP_Clone = <?php echo json_encode( $settings ); ?>
</script>

    <div class="hidden clone-facet">
        <div class="facetwp-row">
            <div class="table-row code-unlock">
                This facet was added with PHP code. Click <span class="dashicons dashicons-unlock"></span> to enable changes.
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
            <h3><?php _e( 'Other settings', 'fwp' ); ?></h3>
            <table class="facet-fields"></table>
        </div>
    </div>

    <div class="hidden clone-template">
        <div class="facetwp-row">
            <div class="table-row code-unlock">
                This template was added with PHP code. Click <span class="dashicons dashicons-unlock"></span> to enable changes.
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
                    <?php _e( 'Which posts would you like to use for the content listing?', 'fwp' ); ?>
                </div>
            </div>
            <div class="media-frame-content">
                <div class="modal-content-wrap">
                    <div class="flexbox">
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
