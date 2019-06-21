<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldGroupV5
 * @package wpai_acf_add_on\acf\fields
 */
class FieldGroupV5 extends Field {

    /**
     *  Field type key
     */
    public $type = 'group';

    /**
     * @var string
     */
    public $supportedVersion = 'v5';

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
        /** @var Field $subField */
        foreach ($this->getSubFields() as $subField){
            $subField->parse($xpath[$subField->getFieldKey()], $parsingData, array(
                'field_path' => $this->getOption('field_path') . "[" . $this->getFieldKey() . "]"
            ));
        }
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
        /** @var Field $subField */
        foreach ($this->getSubFields() as $subField){
            $subField->import($importData, array(
                'container_name' => $this->getFieldName() . "_"
            ));
        }
        ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), '');
    }

    /**
     * @return int
     */
    public function getCountValues() {
        $countRows = 0;
        /** @var Field $field */
        foreach ($this->getSubFields() as $field){
            $field->importData = $this->getImportData();
            $count = $field->getCountValues();
            if ($count > $countRows){
                $countRows = $count;
            }
        }
        return $countRows;
    }

    /**
     * @return bool
     */
    public function getOriginalFieldValueAsString() {
        return false;
    }
}