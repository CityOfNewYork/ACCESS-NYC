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

        if ($is_append_new) {
            add_filter('pmxi_custom_field_to_delete', array($this, 'is_custom_field_to_delete'), 99, 5);
        }

        $parents = $this->getParents();
        if (!empty($parents)){
            $value = '';
            foreach ($parents as $parent) {
                $value = explode($parent['delimiter'], $values[$this->getPostIndex()]);
                $value = $value[$parent['index']];
            }
            $values[$this->getPostIndex()] = $value;
        }

        foreach ($values as $i => $value) {
            $imgs = array();
            $line_imgs = explode("\n", $value);
            if (!empty($line_imgs)) {
                foreach ($line_imgs as $line_img) {
                    $imgs = array_merge($imgs, empty($xpath['delim']) ? array($line_img) : str_getcsv($line_img, $xpath['delim']));
                }
            }
            $values[$i] = array_filter($imgs);
        }

        $gallery_ids = $is_append_new ? ACFService::get_post_meta($this, $this->getPostID(), $this->getFieldName()) : array();
        if (empty($gallery_ids)){
            $gallery_ids = array();
        }
        if (!empty($values[$this->getPostIndex()])) {
            $search_in_gallery = empty($xpath['search_in_media']) ? 0 : 1;
            $search_in_files = empty($xpath['search_in_files']) ? 0 : 1;
            foreach ($values[$this->getPostIndex()] as $url) {
                if ("" != $url and $attid = ACFService::import_image(trim($url), $this->getPostID(), $parsingData['logger'], $search_in_gallery, $search_in_files, $this->importData['articleData']) and !in_array($attid, $gallery_ids)) {
                    $gallery_ids[] = $attid;
                }
            }
        }
        return $gallery_ids;
    }

    /**
     * Do not delete gallery field in case 'Append only new images and do not touch existing during
     * updating gallery field.' option enabled.
     *
     * @param $field_to_delete
     * @param $pid
     * @param $post_type
     * @param $options
     * @param $cur_meta_key
     * @return bool
     */
    function is_custom_field_to_delete($field_to_delete, $pid, $post_type, $options, $cur_meta_key) {
        if ($cur_meta_key == $this->getFieldName()) {
            $field_to_delete = FALSE;
        }
        return $field_to_delete;
    }
}