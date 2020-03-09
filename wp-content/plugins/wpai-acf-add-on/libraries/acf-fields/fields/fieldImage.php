<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldImage
 * @package wpai_acf_add_on\acf\fields
 */
class FieldImage extends Field {

    /**
     *  Field type key
     */
    public $type = 'image';

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

        $xpath = is_array($xpath) ? $xpath['url'] : $xpath;
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
        if ($isUpdated){
            ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $this->getFieldValue());
        }
    }

    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue() {
        $attachmentID = false;
        if ("" != parent::getFieldValue() ) {
            $parsingData = $this->getParsingData();
            $xpath  = $this->getOption('xpath');
            $search_in_gallery = empty($xpath['search_in_media']) ? 0 : 1;
            $search_in_files = empty($xpath['search_in_files']) ? 0 : 1;
            $attachmentID = ACFService::import_image(parent::getFieldValue(), $this->getPostID(), $parsingData['logger'], $search_in_gallery, $search_in_files, $this->importData['articleData']);
        }
        return $attachmentID;
    }
}