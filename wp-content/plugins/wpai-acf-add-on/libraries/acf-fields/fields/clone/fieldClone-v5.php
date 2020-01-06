<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldCloneV5
 * @package wpai_acf_add_on\acf\fields
 */
class FieldCloneV5 extends Field {

    /**
     *  Field type key
     */
    public $type = 'clone';

    /**
     * @var string
     */
    public $supportedVersion = 'v5';

    /**
     *
     * Parse field data
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse($xpath, $parsingData, $args = array()) {
        parent::parse($xpath, $parsingData, $args);
        /** @var Field $subField */
        foreach ($this->getSubFields() as $subField){
            $subField->parse($xpath[$subField->getFieldKey()], $parsingData, array(
                'field_path' => $this->getOption('field_path') . "[" . $this->getFieldKey() . "]"
            ));
        }
    }

    /**
     * @param $importData
     * @param array $args
     * @return mixed
     */
    public function import($importData, $args = array()) {

        $isUpdated = parent::import($importData, $args);
        if (!$isUpdated){
            return FALSE;
        }
        $field = $this->getData('field');
        $prefix = $this->importData['container_name'];
        if ($field['prefix_name']) {
            $prefix = $field['name'] . '_' . $prefix;
        }

        /** @var Field $subField */
        foreach ($this->getSubFields() as $subField){
            $subField->importData = $importData;
            $subField->import($importData, array(
                'container_name' => $prefix
            ));
        }

        ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), '');
    }

    /**
     *  Init cloned fields
     */
    public function initSubFields() {

        // Get sub fields configuration
        $subFieldsData = $this->isLocalFieldStorage() ? $this->getLocalSubFieldsData() : $this->getDBSubFieldsData();

        if ($subFieldsData){
            foreach ($subFieldsData as $subFieldData) {
                if ($subFieldData) {
                    $field = $this->initDataAndCreateField($subFieldData);
                    $this->subFields[] = $field;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getDBSubFieldsData() {
        $fieldsData = array();
        $field = $this->getData('field');
        if (!empty($field['clone'])){
            foreach ($field['clone'] as $sub_field_key) {
                if (strpos($sub_field_key, 'group_') === 0){
                    $acf_groups = get_posts(array(
                        'posts_per_page' => 1,
                        'post_type' => 'acf-field-group',
                        'name' => $sub_field_key,
                        'post_status' => array('publish', 'acf-disabled')
                    ));
                    if (!empty($acf_groups)){
                        foreach ($acf_groups as $acf_group){
                            $groupFields = get_posts(array(
                                'posts_per_page' => -1,
                                'post_type' => 'acf-field',
                                'post_parent' => $acf_group->ID,
                                'post_status' => 'publish',
                                'orderby' => 'menu_order',
                                'order' => 'ASC')
                            );
                            if (!empty($groupFields)){
                                foreach ($groupFields as $groupField){
                                    $fieldsData[] = $groupField;
                                }
                            }
                        }
                    }
                }
                else{
                    $fieldsData[] = $this->getDBFieldDataByKey($sub_field_key);
                }
            }
        }
        return $fieldsData;
    }

    /**
     * @return array
     */
    public function getLocalSubFieldsData() {
        $fieldsData = array();
        $field = $this->getData('field');
        if (!empty($field['clone'])) {
            $fields = [];
            if (function_exists('acf_local')) {
                $fields = acf_local()->fields;
            }
            if (empty($fields) && function_exists('acf_get_local_fields')) {
                $fields = acf_get_local_fields();
            }
            foreach ($field['clone'] as $sub_field_key) {
                if (strpos($sub_field_key, 'group_') === 0){
                    if (!empty($fields)){
                        foreach ($fields as $sub_field) {
                            if ($sub_field['parent'] == $this->getFieldKey()){
                                $sub_fieldData = $sub_field;
                                $sub_fieldData['ID'] = $sub_fieldData['id'] = uniqid();
                                $fieldsData[] = $sub_fieldData;
                            }
                        }
                    }
                }
                else{
                    $fieldsData[] = $this->getLocalFieldDataByKey($sub_field_key);
                }
            }
        }
        return $fieldsData;
    }

    /**
     * @return int
     */
    public function getCountValues() {
        $values = $this->getOption('values');
        $countRows = 0;
        /** @var Field $field */
        foreach ($values as $field){
            if (!empty($field)) {
                $field->importData = $this->getImportData();
                $count = $field->getCountValues();
                if ($count > $countRows){
                    $countRows = $count;
                }
            }
        }
        return $countRows;
    }

    /**
     * @return bool
     */
    public function getOriginalFieldValueAsString() {
        return false;
    }
}