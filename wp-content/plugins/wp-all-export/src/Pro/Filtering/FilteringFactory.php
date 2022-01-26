<?php

namespace Wpae\Pro\Filtering;

/**
 * Class FilteringFactory
 * @package Wpae\Pro\Filtering
 */
class FilteringFactory
{
    public static function getFilterEngine()
    {
        if (\XmlExportEngine::$is_user_export && \XmlExportEngine::get_addons_service()->isUserAddonActive() && PMUE_EDITION == 'paid'){
            if (! empty(\XmlExportEngine::$post_types) and @in_array("shop_customer", \XmlExportEngine::$post_types)){
                return new \Pmue\Pro\Filtering\FilteringCustomers();
            }
            return new \Pmue\Pro\Filtering\FilteringUsers();
        }

        return new FilteringFree();
    }

    public static function render_filtering_block( $engine, $isWizard, $post, $is_on_template_screen = false )
    {

        if ( $isWizard or $post['export_type'] != 'specific' ) return;

        ?>
        <div class="wpallexport-collapsed wpallexport-section closed">
            <div class="wpallexport-content-section wpallexport-filtering-section" <?php if ($is_on_template_screen):?>style="margin-bottom: 10px;"<?php endif; ?>>
                <div class="wpallexport-collapsed-header" style="padding-left: 25px;">
                    <h3><?php esc_html_e('Filtering Options','wp_all_export_plugin');?></h3>
                </div>
                <div class="wpallexport-collapsed-content" style="padding: 0;">
                    <div class="wpallexport-collapsed-content-inner">
                        <?php include_once PMXE_ROOT_DIR . '/views/admin/export/blocks/filters.php'; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}