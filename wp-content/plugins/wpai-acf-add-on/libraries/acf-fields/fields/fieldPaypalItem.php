<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldPaypalItem
 * @package wpai_acf_add_on\acf\fields
 */
class FieldPaypalItem extends Field {

    /**
     *  Field type key
     */
    public $type = 'paypal_item';

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
        $keys = array('item_name', 'item_description', 'price');
        foreach ($keys as $key){
            $values[$key] = $this->getByXPath($xpath[$key]);
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
        $values = $this->getOption('values');
        return array(
            'item_name' => $values['item_name'][$this->getPostIndex()],
            'item_description' => $values['item_description'][$this->getPostIndex()],
            'price' => $values['price'][$this->getPostIndex()]
        );
    }
}