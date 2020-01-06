<?php

namespace Wpae\App\Service\Addons;


class AddonService
{
    public function isUserAddonActive() {
        return defined('PMUE_EDITION');
    }

    public function isUserAddonActiveAndIsUserExport()
    {
        return $this->isUserAddonActive() && \XmlExportUser::$is_active;
    }

    public function userExportsExistAndAddonNotInstalled()
    {

        $exports = new \PMXE_Export_List();
        $exports->getBy('parent_id', 0)->convertRecords();

        foreach ($exports as $item) {

            if (
                ((in_array('users', $item['options']['cpt']) || in_array('shop_customer', $item['options']['cpt'])) && !$this->isUserAddonActive()) ||
                ($item['options']['export_type'] == 'advanced' && $item['options']['wp_query_selector'] == 'wp_user_query' && !$this->isUserAddonActive())
            ) {
                return true;
            }

        }

        return false;
    }

}