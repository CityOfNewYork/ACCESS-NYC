<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldGallery
 * @package wpai_acf_add_on\acf\fields
 */
class FieldGallery extends Field {

    /**
     *  Field type key
     */
    public $type = 'gallery';

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
        if (is_array($xpath)) {
            if (!empty($xpath['gallery'])) {
                $values = $this->getByXPath($xpath['gallery']);
                foreach ($values as $i => $value) {
                    $imgs = array();
                    $line_imgs = explode("\n", $value);
                    if (!empty($line_imgs)) {
                        foreach ($line_imgs as $line_img) {
                            $imgs = array_merge($imgs, empty($xpath['delim']) ? array($line_img) : str_getcsv($line_img, $xpath['delim']));
                        }
                    }
                    $values[$i] = $imgs;
                }
            }
        }
        else {
            $values = $this->getByXPath($xpath);
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
        $parsingData = $this->getParsingData();
        $values = $this->getOption('values');
        $xpath  = $this->getOption('xpath');
        $is_append_new = empty($xpath['only_append_new']) ? 0 : 1;

        $gallery_ids = $is_append_new ? ACFService::get_post_meta($this, $this->getPostID(), $this->getFieldName()) : array();
        if (empty($gallery_ids)){
            $gallery_ids = array();
        }
        if (!empty($values[$this->getPostIndex()])) {
            $search_in_gallery = empty($xpath['search_in_media']) ? 0 : 1;
            $search_in_files = empty($xpath['search_in_files']) ? 0 : 1;
            foreach ($values[$this->getPostIndex()] as $url) {
                if ("" != $url and $attid = ACFService::import_image(trim($url), $this->getPostID(), $parsingData['logger'], $search_in_gallery, $search_in_files) and !in_array($attid, $gallery_ids)) {
                    $gallery_ids[] = $attid;
                }
            }
        }
        return $gallery_ids;
    }
}