<?php
if(!defined('ABSPATH')) {
    die();
}
/** @var $post */
/** @var string $random */
$random = uniqid();
?>
<div class="product_variations">
    <h4 style="margin-top: 20px;"><?php esc_html_e('Product Variations', 'wp_all_export_plugin'); ?>
        <a href="#help" class="wpallexport-help"
         style="position: relative; top: 0px;"
         title="<?php esc_html_e('WooCommerce stores each product variation as a separate product in the database, along with a parent product to tie all of the variations together.<br/><br/>If the product title is \'T-Shirt\', then the parent product will be titled \'T-Shirt\', and in the database each size/color combination will be a separate product with a title like \'Variation #23 of T-Shirt\'.', 'wp_all_export_plugin'); ?>">?</a></h4>
    <div class="input" style="display: inline-block; width: 100%;">
        <div>
            <label>
                <input disabled type="radio" class="export_variations <?php if (PMXE_EDITION != 'paid') {
                    echo "variations_disabled";
                } ?>"
                       value="<?php echo XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION; ?>"
                       name="<?php echo esc_attr($random); ?>_export_variations"/><?php esc_html_e("Export product variations and their parent products", 'wp_all_export_plugin'); ?>
            </label>
            <div style="display: none;" class="sub-options sub-options-<?php echo XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION;?>">
                <label style="display: block; margin-bottom: 8px;">
                    <input type="radio" disabled
                           name="<?php echo esc_attr($random); ?>_export_variations_title_1"
                           value="<?php echo XmlExportEngine::VARIATION_USE_PARENT_TITLE; ?>"

                           class="export_variations_title">
                    <?php esc_html_e("Product variations use the parent product title", 'wp_all_export_plugin');?>
                </label>
                <div class="clear"></div>
                <label style="display: block; margin-bottom: 8px;">
                    <input type="radio"
                           name="<?php echo esc_attr($random); ?>_export_variations_title_1"
                           value="<?php echo XmlExportEngine::VARIATION_USE_DEFAULT_TITLE; ?>"

                           class="export_variations_title" disabled>
                    <?php esc_html_e("Product variations use the default variation product title", 'wp_all_export_plugin'); ?>
                </label>
            </div>
        </div>
        <div class="clear"></div>
        <div style="margin: 6px 0;">
            <label>
                <input disabled type="radio" class="export_variations"
                          value="<?php echo XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_VARIATION; ?>"
                          name="<?php echo esc_attr($random); ?>_export_variations"/><?php esc_html_e("Only export product variations", 'wp_all_export_plugin'); ?>
            </label>
            <div class="sub-options sub-options-<?php echo XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_VARIATION; ?>">
                <label style="display: block; margin-bottom: 8px;">
                    <input disabled type="radio"
                              name="<?php echo esc_attr($random); ?>_export_variations_title_2"
                              value="<?php echo XmlExportEngine::VARIATION_USE_PARENT_TITLE; ?>"
                              <?php if($post['export_variations_title'] == XmlExportEngine::VARIATION_USE_PARENT_TITLE) {?>
                                  checked="checked"
                              <?php }?>
                              class="export_variations_title">
                    <?php esc_html_e("Product variations use the parent product title", 'wp_all_export_plugin'); ?>
                </label>
                <div class="clear"></div>
                <label>
                    <input disabled type="radio"
                              name="<?php echo esc_attr($random); ?>_export_variations_title_2"
                              value="<?php echo XmlExportEngine::VARIATION_USE_DEFAULT_TITLE; ?>"
                              <?php if($post['export_variations_title'] == XmlExportEngine::VARIATION_USE_DEFAULT_TITLE) {?>
                                  checked="checked"
                              <?php } ?>
                              class="export_variations_title">
                    <?php esc_html_e("Product variations use the default variation product title", 'wp_all_export_plugin'); ?>
                </label>
            </div>
        </div>
        <div class="clear"></div>
        <div style="margin: 6px 0;">
            <label>
                <input type="radio" checked="checked" disabled class="export_variations <?php if (PMXE_EDITION != 'paid') {
                    echo "variations_disabled";
                } ?>"
                          value="<?php echo XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT; ?>"
                          name="<?php echo esc_attr($random)?>_export_variations"/><?php esc_html_e("Only export parent products", 'wp_all_export_plugin'); ?>
            </label>
        </div>

        <?php if(XmlExportEngine::get_addons_service()->isWooCommerceAddonActive()) { ?>
        <div class="wpallexport-free-edition-notice" style="padding: 20px; margin-bottom: 10px;">
            <a class="upgrade_link" style="font-size:15px !important;" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&amp;download_id=2707173&amp;edd_options%5Bprice_id%5D=1&amp;utm_source=export-plugin-free&amp;utm_medium=upgrade-notice&amp;utm_campaign=variation_options">
                Upgrade to the Pro edition of WP All Export to filter variable products</a>
            <p>If you already own it, remove the free edition and install the Pro edition.</p>
        </div>
        <?php } else { ?>
            <div class="wpallexport-free-edition-notice" style="padding: 20px 20px 35px 20px; margin-bottom: 10px;">
                <p>The WooCommerce Export Package is required to export variations.</p>
                <p>
                <a style="margin: 1em 0; font-size:1em !important" class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=4206899&edd_options%5Bprice_id%5D=1&utm_source=export-plugin-free&utm_medium=upgrade-notice&utm_campaign=export-variations">
                Purchase the WooCommerce Export Package</a>
                </p>
            </div>
        <?php } ?>
    </div>
</div>