<?php

namespace wpai_acf_add_on\acf\groups;

use wpai_acf_add_on\acf\fields\Field;
use wpai_acf_add_on\acf\fields\FieldFactory;

require_once(__DIR__.'/groupInterface.php');

define('PMAI_GROUPS_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));

/**
 * Class Group
 * @package wpai_acf_add_on\acf\groups
 */
abstract class Group implements GroupInterface {

    /**
     * @var
     */
    public $post;

    /**
     * @var
     */
    public $group;

    /**
     * @var array
     */
    public $fields = array();

    /**
     * @var array
     */
    public $fieldsData = array();

    /**
     * Group constructor.
     * @param $group
     */
    public function __construct($group, $post) {
        $this->group = $group;
        $this->post = $post;
        $this->initFields();
    }

    /**
     *  Create field instances
     */
    public function initFields(){
        foreach ($this->getFieldsData() as $fieldData){
            $field = FieldFactory::create($fieldData, $this->getPost());
            $this->fields[] = $field;
        }
    }

    /**
     * @return array
     */
    public function getFieldsData()
    {
        return $this->fieldsData;
    }

    /**
     * @return array
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * @return mixed
     */
    public function getPost() {
        return $this->post;
    }

    /**
     *  Render group
     */
    public function view() {
        $this->renderHeader();
        foreach ($this->getFields() as $field){
            $field->view();
        }
        $this->renderFooter();
    }

    /**
     *  Render group header
     */
    protected function renderHeader(){
        $filePath = __DIR__ . '/templates/header.php';
        if (is_file($filePath)) {
            extract($this->group);
            include $filePath;
        }
    }

    /**
     *  Render group footer
     */
    protected function renderFooter(){
        $filePath = __DIR__ . '/templates/footer.php';
        if (is_file($filePath)) {
            include $filePath;
        }
    }

    /**
     * @param $parsingData
     * @return array
     */
    public function parse($parsingData) {
        /** @var Field $field */
        foreach ($this->getFields() as $field){
            $xpath = empty($parsingData['import']->options['fields'][$field->getFieldKey()]) ? "" : $parsingData['import']->options['fields'][$field->getFieldKey()];
            $field->parse($xpath, $parsingData);
        }
    }

    /**
     * @param $importData
     * @param array $args
     */
    public function import($importData, $args = array()) {
        /** @var Field $field */
        foreach ($this->getFields() as $field){
            $field->import($importData, $args);
        }
    }

    /**
     * @param $importData
     */
    public function saved_post($importData){
        /** @var Field $field */
        foreach ($this->getFields() as $field){
            $field->saved_post($importData);
        }
    }
}