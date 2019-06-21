<?php

namespace wpai_acf_add_on\acf\fields;

require_once 'fieldGoogleMap.php';

/**
 * Class FieldGoogleMapExtended
 * @package wpai_acf_add_on\acf\fields
 */
class FieldGoogleMapExtended extends FieldGoogleMap {

    /**
     *  Field type key
     */
    public $type = 'google_map_extended';

    /**
     * @return array
     */
    public function getFieldValue()
    {
        $this->getAddress();
        $values = $this->getOption('values');
        return array(
            'address' => $values['address'][$this->getPostIndex()],
            'lat' => $values['lat'][$this->getPostIndex()],
            'lng' => $values['lng'][$this->getPostIndex()],
            'zoom' => $values['zoom'][$this->getPostIndex()],
            'center_lat' => $values['center_lat'][$this->getPostIndex()],
            'center_lng' => $values['center_lng'][$this->getPostIndex()]
        );
    }
}