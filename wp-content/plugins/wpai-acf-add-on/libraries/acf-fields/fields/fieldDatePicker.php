<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldDatePicker
 * @package wpai_acf_add_on\acf\fields
 */
class FieldDatePicker extends Field {

    /**
     *  Field type key
     */
    public $type = 'date_picker';

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

        if ("" != $xpath) {
            $values = $this->getByXPath($xpath);
            foreach ($values as $i => $d) {
                if ($d == 'now') {
                    $d = current_time('mysql');
                } // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
                $time = strtotime($d);
                if (FALSE === $time) {
                    $values[$i] = $d;
                }
                else{
                    $values[$i] = date('Ymd', $time);
                }
            }
            $this->setOption('values', $values);
        }
    }

    /**
     * @param $importData
     * @param array $args
     * @return void
     */
    public function import($importData, $args = array()) {
        $isUpdated = parent::import($importData, $args);
        if ($isUpdated){
            $field = $this->getData('field');
            $time = strtotime($this->getFieldValue());
            if (FALSE === $time) {
                $value = $this->getFieldValue();
            }
            else{
                $value = isset($field['date_format']) ? date($this->massageDateFormat($field['date_format']), $time) : date('Ymd', $time);
            }
            ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $value);
        }
    }

    /**
     *
     * Convert js date format into PHP date format.
     *
     * @param $format
     *
     * @return mixed
     */
    private function massageDateFormat($format){
        return str_replace(array('yy', 'mm', 'dd'), array('Y','m','d'), $format);
    }
}