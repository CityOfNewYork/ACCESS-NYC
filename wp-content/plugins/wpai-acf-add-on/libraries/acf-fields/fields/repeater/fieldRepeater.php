<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldRepeater
 * @package wpai_acf_add_on\acf\fields
 */
class FieldRepeater extends Field {

    /**
     *  Field type key
     */
    public $type = 'repeater';

    /**
     * @var string
     */
    public $mode = 'csv';

    /**
     * @var string
     */
    public $delimiter = ',';

    /**
     * @var bool
     */
    public $ignoreEmpties = false;

    /**
     * @var int
     */
    public $rowIndex = 0;

    /**
     * @return string
     */
    public function getMode() {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode($mode) {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getDelimiter() {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter($delimiter) {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getRowIndex() {
        return $this->rowIndex;
    }

    /**
     * @param string $index
     */
    public function setRowIndex($index) {
        $this->rowIndex = $index;
    }

    /**
     * @return boolean
     */
    public function isIgnoreEmpties() {
        return $this->ignoreEmpties;
    }

    /**
     * @param boolean $ignoreEmpties
     */
    public function setIgnoreEmpties($ignoreEmpties) {
        $this->ignoreEmpties = $ignoreEmpties;
    }

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

        // Remove repeater row template.
        if (isset($xpath['rows']['ROWNUMBER'])) {
            unset($xpath['rows']['ROWNUMBER']);
        }

        if (!empty($xpath['rows'])) {

            $values = array();

            $is_ignore_empties = empty($xpath['is_ignore_empties']) ? false : true;

            $this->setIgnoreEmpties($is_ignore_empties);

            switch ($xpath['is_variable']){
                case 'yes':
                    $rowFields = array_shift($xpath['rows']);
                    $this->setMode('xml');
                    for ($k = 0; $k < $this->getOption('count'); $k++) {

                        $repeaterXpath = '[' . ($k + 1) . ']/' . ltrim(trim($xpath['foreach'], '{}!'), '/');
                        $file = false;
                        $repeaterRows = \XmlImportParser::factory($this->parsingData['xml'], $this->getOption('base_xpath') . $repeaterXpath, "{.}", $file)->parse();
                        @unlink($file);

                        $xpath_suffix = '';
                        if ((!isset($rowFields[$this->getFieldKey()]) || (is_array($rowFields[$this->getFieldKey()]) || strpos($rowFields[$this->getFieldKey()], "!") !== 0)) && strpos($xpath['foreach'], "!") !== 0){
                            $xpath_suffix = $this->getOption('base_xpath') . $repeaterXpath;
                            $xpath_suffix = str_replace($parsingData['xpath_prefix'] . $parsingData['import']->xpath, '', $xpath_suffix);
                        }

                        $rowData = array();
                        /** @var Field $subField */
                        foreach ($this->getSubFields() as $subField){
                            $subField->parse($rowFields[$subField->getFieldKey()], $parsingData, array(
                                'field_path' => $this->getOption('field_path') . "[" . $this->getFieldKey() . "][rows][1]",
                                'xpath_suffix' => $xpath_suffix,
                                'repeater_count_rows' => count($repeaterRows),
                                'inside_repeater' => true
                            ));
                            $rowData[$subField->getFieldKey()] = clone $subField;
                        }
                        $values[] = array(
                            'countRows' => count($repeaterRows),
                            'fields' => $rowData
                        );
                    }
                    break;
                default:
                    switch ($xpath['is_variable']){
                        case 'csv':
                            $this->setDelimiter($xpath['separator']);
                            $this->setIgnoreEmpties(true);
                            break;
                        default:
                            $this->setDelimiter(false);
                            $this->setMode('fixed');
                            break;
                    }
                    foreach ($xpath['rows'] as $key => $rowFields) {
                        $rowData = array();
                        $subFields = $this->getSubFields();
                        /** @var Field $subField */
                        foreach ($subFields as $subField){
                            if (isset($rowFields[$subField->getFieldKey()])) {
                                $subField->parse($rowFields[$subField->getFieldKey()], $parsingData, array(
                                    'field_path' => $this->getOption('field_path') . "[" . $this->getFieldKey() . "][rows][" . $key . "]",
                                    'xpath_suffix' => empty($args['xpath_suffix']) ? '' : $args['xpath_suffix'],
                                    'repeater_count_rows' => 0,
                                    'inside_repeater' => true
                                ));
                                $rowData[$subField->getFieldKey()] = clone $subField;
                            }
                        }
                        $values[] = $rowData;
                    }
                    break;
            }
            $this->setOption('values', $values);
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

        $values = $this->getOption('values');

        if (!empty($values)){
            switch ($this->getMode()) {
                case 'xml':
                    $countRows = 0;
                    for ($k = 0; $k < $values[$this->getPostIndex()]['countRows']; $k++) {
                        $importData['i'] = $k;
                        // Init importData in all sub fields.
                        /** @var Field $subField */
                        foreach ($values[$this->getPostIndex()]['fields'] as $subFieldKey => $subField) {
                            $subField->importData = $importData;
                        }
                        if ($this->isImportRow($values[$this->getPostIndex()]['fields'])) {
                            /** @var Field $subField */
                            foreach ($values[$this->getPostIndex()]['fields'] as $subFieldKey => $subField) {
                                $subField->import($importData, array(
                                    'container_name' => $this->getFieldName() . "_" . $countRows . "_"
                                ));
                            }
                            $countRows++;
                        }
                    }
                    ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $countRows);
                    break;
                case 'csv':
                    $countRows = 0;
                    $fields = array_shift($values);
                    if (!empty($fields)) {
                        // Init importData in all sub fields
                        /** @var Field $subField */
                        foreach ($fields as $subFieldKey => $subField) {
                            $subField->importData = $importData;
                        }
                        if ($this->isImportRow($fields)) {
                            $countRows = $this->getCountRows($fields);
                            for ($k = 0; $k < $countRows; $k++) {
                                $this->setRowIndex($k);
                                /** @var Field $subField */
                                foreach ($fields as $subFieldKey => $subField) {
                                    $parentField = $subField->getParent();
                                    if ($parentField) {
                                        $parentField->setRowIndex($k);
                                    }
                                    $subField->import($importData, array(
                                        'container_name' => $this->getFieldName() . "_" . $k . "_"
                                    ));
                                }
                            }
                        }
                    }
                    ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $countRows);
                    break;
                case 'fixed':
                    $countRows = 0;
                    foreach ($values as $row_number => $fields) {
                        if (!empty($fields)) {
                            $countRows++;
                            // Init importData in all sub fields
                            /** @var Field $subField */
                            foreach ($fields as $subFieldKey => $subField) {
                                $subField->importData = $importData;
                            }
                            if ($this->isImportRow($fields)) {
                                /** @var Field $subField */
                                foreach ($fields as $subFieldKey => $subField) {
                                    $subField->import($importData, array(
                                        'container_name' => $this->getFieldName() . "_" . ($countRows - 1) . "_"
                                    ));
                                }
                            }
                            else {
                                $countRows--;
                            }
                        }
                    }
                    ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $countRows);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @param $fields
     * @return bool
     */
    protected function isImportRow($fields){
        $isImportRow = $this->isIgnoreEmpties() ? false : true;
        if (!$isImportRow){
            /** @var Field $field */
            foreach ($fields as $field){
                if ($field->isNotEmpty()){
                    $isImportRow = true;
                    break;
                }
            }
        }
        return $isImportRow;
    }

    /**
     * @param $fields
     * @return int
     */
    protected function getCountRows($fields){
        $countRows = 0;
        /** @var Field $field */
        foreach ($fields as $field){
            if ($field->getType() == 'repeater') continue;
            $field->importData = $this->getImportData();
            $count = $field->getCountValues();
            if ($count > $countRows){
                $countRows = $count;
            }
        }
        return $countRows;
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