<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldPostObject
 * @package wpai_acf_add_on\acf\fields
 */
class FieldPostObject extends Field {

    /**
     *  Field type key
     */
    public $type = 'post_object';

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
        $xpath = is_array($xpath) ? $xpath['value'] : $xpath;
        $values = $this->getByXPath($xpath);
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

        $xpath = $this->getOption('xpath');

        $values = parent::getFieldValue();

        if (!is_array($values)){
            $delimiter = empty($xpath['delim']) ? ',' : $xpath['delim'];
            $values = explode($delimiter, $values);
        }

        $post_ids = ACFService::get_posts_by_relationship($values, $this->getFieldOption('post_type'));

        if (!empty($post_ids)) {
            $parsedData = $this->getParsedData();
            return empty($parsedData['multiple']) ? array_shift($post_ids) : $post_ids;
        }
        return '';
    }
}