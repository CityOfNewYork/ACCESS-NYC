<?php

namespace wpai_acf_add_on\acf\groups;

require_once(__DIR__.'/group.php');

/**
 * Class GroupV4Local
 * @package wpai_acf_add_on\acf\groups
 */
class GroupV4Local extends Group {

    /**
     *  Init group fields which are saved locally in the code for ACF v4.x
     */
    public function initFields() {

        global $acf_register_field_group;

        if (!empty($acf_register_field_group)) {
            foreach ($acf_register_field_group as $key => $group) {
                if ($group['id'] == $this->group['ID']) {
                    $this->fieldsData = $group['fields'];
                    break;
                }
            }
        }
        // create field instances
        parent::initFields();
    }
}