<?php

namespace wpai_acf_add_on\acf\groups;

require_once(__DIR__.'/group.php');

/**
 * Class GroupV5Local
 * @package wpai_acf_add_on\acf\groups
 */
class GroupV5Local extends Group {

    /**
     *  Init group fields which are saved locally in the code for ACF v5.x
     */
    public function initFields() {
        $fields = [];
        if (function_exists('acf_local')) {
            $fields = acf_local()->fields;
        }
        if (empty($fields) && function_exists('acf_get_local_fields')) {
            $fields = acf_get_local_fields();
        }
        // Re-init ACF group in case it was defined in ACF 4.x
        if (isset($this->group['ID'])) {
            $groups = [];
            if (function_exists('acf_local')) {
                $groups = acf_local()->groups;
            }
            if (empty($groups) && function_exists('acf_get_local_field_groups')) {
                $groups = acf_get_local_field_groups();
            }
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    if (isset($group['id']) && $group['id'] == $this->group['ID']) {
                        $this->group['ID'] = $group['key'];
                    }
                }
            }
        }
        if (!empty($fields)) {
            foreach ($fields as $key => $field) {
                if ($field['parent'] == $this->group['ID']) {
                    $fieldData = $field;

                    $fieldData['ID'] = $fieldData['id'] = uniqid();
                    $fieldData['label'] = $field['label'];
                    $fieldData['key'] = $field['key'];
                    $this->fieldsData[] = $fieldData;
                }
            }
        }        
        // create field instances
        parent::initFields();
    }
}