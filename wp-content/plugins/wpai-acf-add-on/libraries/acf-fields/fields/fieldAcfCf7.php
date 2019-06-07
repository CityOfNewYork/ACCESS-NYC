<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldAcfCf7
 * @package wpai_acf_add_on\acf\fields
 */
class FieldAcfCf7 extends Field {

    /**
     *  Field type key
     */
    public $type = 'acf_cf7';

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
        $values = $this->getByXPath($xpath);
        $this->setOption('values', $values);
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
    public function getFieldValue(){
        return $this->getOption('is_multiple_field') ? explode(",", parent::getFieldValue()) : parent::getFieldValue();
    }
}