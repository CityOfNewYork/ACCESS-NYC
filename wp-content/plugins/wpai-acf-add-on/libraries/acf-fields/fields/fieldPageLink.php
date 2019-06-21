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
        if ( ! empty($entries) and is_array($entries) ) {
            foreach ($entries as $ev) {
                $args = array(
                    'name' => $ev,
                    'post_type' => 'any',
                    'post_status' => 'any',
                    'numberposts' => 1
                );
                $my_posts = get_posts($args);
                if ($my_posts) {
                    $post_ids[] = get_permalink($my_posts[0]->ID);
                }
                elseif (ctype_digit($ev)) {
                    $my_post = get_post($ev);
                    if ($my_post) {
                        $post_ids[] = get_permalink($my_post->ID);
                    }
                }
            }
        }
        if (!empty($post_ids)) {
            $parsedData = $this->getParsedData();
            return empty($parsedData['multiple']) ? array_shift($post_ids) : $post_ids;
        }
        return '';
    }
}