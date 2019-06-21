<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldSelect
 * @package wpai_acf_add_on\acf\fields
 */
class FieldSelect extends Field {

    /**
     *  Field type key
     */
    public $type = 'select';

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
        switch ($this->getOption('is_multiple_field')){
            case 'yes':
                $values = array_fill(0, $this->getOption('count'), $this->getOption('multiple_value'));
                if (is_array($this->getOption('multiple_value'))){
                    $this->setOption('is_multiple', TRUE);
                }
                break;
            default:
                if ("" != $this->getOption('xpath')) {
                    $field = $this->getData('field');
                    $values = $this->getByXPath($this->getOption('xpath'));

                    if (!empty($field['multiple']) || isset($field['field_type']) && in_array($field['field_type'], array('checkbox', 'multi_select'))) {
                        foreach ($values as $key => $value) {
                            $values[$key] = array_map('trim', explode(",", $value));
                        }
                        $this->setOption('is_multiple', TRUE);
                    }
                }
                break;
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
        ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $this->getFieldValue());
    }

    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue() {

        $value = parent::getFieldValue();

        $parsedData = $this->getParsedData();

        $field_post = get_post($parsedData['id']);
        if ($field_post){
            $field_post_options = unserialize($field_post->post_content);
            if (!empty($field_post_options['save_custom'])){
                $current_choice = (!empty($value) and is_array($value)) ? $value : array();
                foreach ($current_choice as $choice){
                    if (!isset($field_post_options['choices'][$choice])){
                        $field_post_options['choices'][$choice] = $choice;
                    }
                }
                wp_update_post(array(
                    'post_content' => maybe_serialize($field_post_options),
                    'ID' => $parsedData['id']
                ));
            }
        }

        if ($parsedData['is_multiple']) {
            $value = (!empty($value) && is_array($value)) ? $value : array();
        }
        return $value;
    }
}