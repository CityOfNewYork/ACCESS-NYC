<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldPageLink
 * @package wpai_acf_add_on\acf\fields
 */
class FieldPageLink extends Field {

    /**
     *  Field type key
     */
    public $type = 'page_link';

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
        $post_ids = array();
        $entries = explode(",", parent::getFieldValue());
        $field = $this->getData('field');
        if ( ! empty($entries) and is_array($entries) ) {
            $entries = array_map('trim', $entries);
            $entries = array_filter($entries);
            foreach ($entries as $ev) {
                $args = array(
                    'name' => $ev,
                    'post_type' => empty($field['post_type']) ? 'any' : $field['post_type'],
                    'post_status' => 'any',
                    'numberposts' => 1
                );
                $my_posts = get_posts($args);
                if ($my_posts) {
                    $post_ids[] = $my_posts[0]->ID;
                }
                elseif (ctype_digit($ev)) {
                    $args = array(
                      'post_type' => empty($field['post_type']) ? 'any' : $field['post_type'],
                      'numberposts' => 1,
                      'post__in'=> [$ev]
                    );
                    $my_posts = get_posts($args);
                    if ($my_posts) {
                      $post_ids[] = $my_posts[0]->ID;
                    }
                }
            }
        }
        if (!empty($post_ids)) {
            return empty($field['multiple']) ? array_shift($post_ids) : $post_ids;
        }
        return '';
    }
}