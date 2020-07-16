<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

require_once PMAI_ROOT_DIR . '/libraries/acf-fields/base/baseGoogleMap.php';

/**
 * Class FieldGoogleMap
 * @package wpai_acf_add_on\acf\fields
 */
class FieldGoogleMap extends BaseGoogleMap {

    /**
     *  Field type key
     */
    public $type = 'google_map';

    /**
     * @var array
     */
    public $keys = array('address', 'lat', 'lng', 'zoom', 'street_number', 'street_name', 'street_short_name', 'city', 'state', 'state_short', 'post_code', 'country', 'country_short', 'place_id');

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
            $fieldXpath = isset($xpath[$key]) ? $xpath[$key] : '';
            $values[$key] = $this->getByXPath($fieldXpath);
        }

        switch ($xpath['address_geocode']) {
            case 'address_no_key':
                $values['api_key']   = array_fill(0, $this->getOption('count'), "");
                $values['client_id'] = array_fill(0, $this->getOption('count'), "");
                $values['signature'] = array_fill(0, $this->getOption('count'), "");
                break;
            case 'address_google_developers':
                $values['api_key'] = $this->getByXPath($xpath['address_google_developers_api_key']);
                break;
            case 'address_google_for_work':
                $values['client_id'] = $this->getByXPath($xpath['address_google_for_work_client_id']);
                $values['signature'] = $this->getByXPath($xpath['address_google_for_work_digital_signature']);
                break;
            default:
                # code...
                break;
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
     * @return array
     */
    public function getFieldValue() {
        $this->getAddress();
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
        return array(
            'address' => $values['address'][$this->getPostIndex()],
            'lat' => $values['lat'][$this->getPostIndex()],
            'lng' => $values['lng'][$this->getPostIndex()],
            'zoom' => empty($values['zoom'][$this->getPostIndex()]) ? 14 : $values['zoom'][$this->getPostIndex()],
            'street_number' => $values['street_number'][$this->getPostIndex()],
            'street_name' => $values['street_name'][$this->getPostIndex()],
            'street_short_name' => $values['street_short_name'][$this->getPostIndex()],
            'city' => $values['city'][$this->getPostIndex()],
            'state' => $values['state'][$this->getPostIndex()],
            'state_short' => $values['state_short'][$this->getPostIndex()],
            'post_code' => $values['post_code'][$this->getPostIndex()],
            'country' => $values['country'][$this->getPostIndex()],
            'country_short' => $values['country_short'][$this->getPostIndex()],
            'place_id' => $values['place_id'][$this->getPostIndex()],
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
                $parentIndex = false;
                foreach ($parents as $key => $parent) {
                    if ($parentIndex !== false){
                        $value = $value[$parentIndex];
                    }
                    $value = explode($parent['delimiter'], $value);
                    $parentIndex = $parent['index'];
                }
                if (count($value) > $count) {
                    $count = count($value);
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