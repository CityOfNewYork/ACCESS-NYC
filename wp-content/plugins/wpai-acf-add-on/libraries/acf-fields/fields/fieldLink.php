<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldLink
 * @package wpai_acf_add_on\acf\fields
 */
class FieldLink extends Field {

    /**
     *  Field type key
     */
    public $type = 'link';

    /**
     * @var array
     */
    public $keys = array('title', 'url', 'target');

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
        foreach ($this->keys as $key){
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
        global $wpdb;
        $values = $this->getOption('values');
        $parents = $this->getParents();
        if (!empty($parents)){
            foreach ($this->keys as $key){
                $value = '';
                foreach ($parents as $parent) {
                    if (!empty($parent['delimiter'])) {
                        $value = explode($parent['delimiter'], $values[$key][$this->getPostIndex()]);
                        $value = $value[$parent['index']];
                    } else {
                        $value = $values[$key][$this->getPostIndex()];
                    }
                }
                $values[$key][$this->getPostIndex()] = $value;
            }
        }
        // prepare permalink in case if it's non external URL
        if ( ! empty($values['url'][$this->getPostIndex()]) && ! preg_match('%^https?://%i', $values['url'][$this->getPostIndex()]) ){
            $relationID = $values['url'][$this->getPostIndex()];
            if ( ! is_numeric($relationID) ){
                $sql = "SELECT * FROM {$wpdb->posts} WHERE post_type != %s AND ( post_title = %s OR post_name = %s )";
                $relation = $wpdb->get_row($wpdb->prepare($sql, 'revision', $relationID, sanitize_title_for_query($relationID)));
                if ($relation){
                    $relationID = $relation->ID;
                }
            }
            $field['values']['url'][$this->getPostIndex()] = get_permalink($relationID);
        }
        return array(
            'title'  => $values['title'][$this->getPostIndex()],
            'url'    => $values['url'][$this->getPostIndex()],
            'target' => $values['target'][$this->getPostIndex()]
        );
    }

    /**
     * @return int
     */
    public function getCountValues() {
        $parents = $this->getParents();
        $count = 0;
        if (!empty($parents)){
            $values = $this->getOption('values');
            foreach ( $this->keys as $field_key){
                $value = $values[$field_key][$this->getPostIndex()];
                if ($value != "") {
                    $parentIndex = false;
                    foreach ($parents as $key => $parent) {
                        if ($parentIndex !== false){
                            $value = $value[$parentIndex];
                        }
                        if (!empty($parent['delimiter'])) {
	                        $value = explode($parent['delimiter'], $value);
                        }
                        $parentIndex = $parent['index'];
                    }
                    if (!is_array($value)) {
	                    $value = [$value];
                    }
                    $value = array_filter($value);
                    if (count($value) > $count) {
                        $count = count($value);
                    }
                }
            }
        }
        return $count;
    }

    /**
     * @return bool
     */
    public function getOriginalFieldValueAsString() {
        return false;
    }
}