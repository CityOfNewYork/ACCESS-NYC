<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldDateTimePicker
 * @package wpai_acf_add_on\acf\fields
 */
class FieldDateTimePicker extends Field {

    /**
     *  Field type key
     */
    public $type = 'date_time_picker';

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
                } else {
                    $values[$i] = ACFService::isACFNewerThan('5.0.0') ? date('Y-m-d H:i:s', $time) : $time;
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
            ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $this->getFieldValue());
        }
    }

    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue() {
        $time = strtotime(parent::getFieldValue());
        if (FALSE === $time) {
            $value = parent::getFieldValue();
        }
        else{
            $value = ACFService::isACFNewerThan('5.0.0') ? date('Y-m-d H:i:s', $time) : $time;
        }
        return $value;
    }
}