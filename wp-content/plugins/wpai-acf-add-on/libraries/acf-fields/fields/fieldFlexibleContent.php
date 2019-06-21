<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldFlexibleContent
 * @package wpai_acf_add_on\acf\fields
 */
class FieldFlexibleContent extends Field {

    /**
     *  Field type key
     */
    public $type = 'flexible_content';

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

        $values = array();

        if (!empty($xpath['layouts']) and count($xpath['layouts']) > 1) {

            $field = $this->getData('field');

            $values = array();

            unset($xpath['layouts']['ROWNUMBER']);

            foreach ($xpath['layouts'] as $key => $layout_fields) {

                $row_array = array();

                $current_field = FALSE;

                foreach ($field['layouts'] as $layout) {
                    if ($layout['name'] == $layout_fields['acf_fc_layout']) {
                        $current_field = $layout;
                        break;
                    }
                }

                $row_array['acf_fc_layout'] = $layout_fields['acf_fc_layout'];

                if (!empty($current_field['sub_fields']) and is_array($current_field['sub_fields'])) {
                    foreach ($current_field['sub_fields'] as $n => $sub_field) {
                        $childField = FieldFactory::create($sub_field, $this->getData('post'), $this->getOption('field_path') . "[" . $field['key'] . "][layouts][" . $key . "]", $this);
                        $childField->parse($layout_fields[$sub_field['key']], $this->parsingData, array(
                          'field_path' => $this->getOption('field_path') . "[" . $field['key'] . "][layouts][" . $key . "]"
                        ));
                        $row_array['fields'][$sub_field['key']] = $childField;
                    }
                }
                $values[] = $row_array;
            }
        }
        $this->setOption('values', $values);
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
        $values = $this->getOption('values');
        $layouts = array();
        foreach ($values as $layout_number => $layout) {
            if (!empty($layout['fields'])) {
                $layouts[] = $layout['acf_fc_layout'];
                /** @var Field $sub_field */
                foreach ($layout['fields'] as $sub_field_key => $sub_field) {
                    $sub_field->import($importData, array('container_name' => $this->getFieldName() . "_" . $layout_number . "_"));
                }
            }
        }
        ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $layouts);
    }

    /**
     * @return int
     */
    public function getCountValues() {
        $values = $this->getOption('values');
        $countRows = 0;
        foreach ($values as $layout_number => $layout) {
            if (!empty($layout['fields'])) {
                /** @var Field $sub_field */
                foreach ($layout['fields'] as $sub_field_key => $sub_field) {
                    $sub_field->importData = $this->getImportData();
                    $count = $sub_field->getCountValues();
                    if ($count > $countRows){
                        $countRows = $count;
                    }
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