<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

require_once(__DIR__.'/fieldInterface.php');

define('PMAI_FIELDS_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));

/**
 * Class Field
 * @package wpai_acf_add_on\acf\fields
 */
abstract class Field implements FieldInterface {

    /**
     * field type
     */
    public $type;

    /**
     * @var array
     */
    public $data;

    /**
     * @var bool
     */
    public $supportedVersion = false;

    /**
     * @var array
     */
    public $parsingData;

    /**
     * @var array
     */
    public $importData;

    /**
     * @var array
     */
    public $options = array();

    /**
     * @var Field
     */
    public $parent;

    /**
     * @var array
     */
    public $subFields = array();

    /**
     * Field constructor.
     * @param $field
     * @param $post
     * @param $field_name
     * @param $parent_field
     */
    public function __construct($field, $post, $field_name = "", $parent_field = false) {
        $this->data = array(
            'field' => $field,
            'post' => $post,
            'field_name' => $field_name
        );
        $this->setParent($parent_field);
        $this->data = array_merge($this->data, $this->getFieldData());
        $this->initSubFields();
    }

    /**
     *  Create sub field instances
     */
    public function initSubFields(){

        // Get sub fields configuration
        $subFieldsData = $this->isLocalFieldStorage() ? $this->getLocalSubFieldsData() : $this->getDBSubFieldsData();

        if ($subFieldsData){

            foreach ($subFieldsData as $subFieldData) {
                $field = $this->initDataAndCreateField($subFieldData);
                $this->subFields[] = $field;
            }
        }

        // Init sub fields for Flexible Content
        if (ACFService::isACFNewerThan('5.0.0') && $this->getType() == 'flexible_content') {
            // get flexible field
            $flexibleField = $this->getData('field');
            // vars
            $flex_fields = acf_get_fields($flexibleField);
            // loop through layouts, sub fields and swap out the field key with the real field
            foreach (array_keys($flexibleField['layouts']) as $fi) {
                // extract layout
                $layout = acf_extract_var($flexibleField['layouts'], $fi);
                // append sub fields
                if (!empty($flex_fields)) {
                    foreach (array_keys($flex_fields) as $fk) {
                        // check if 'parent_layout' is empty
                        if (empty($flex_fields[$fk]['parent_layout'])) {
                            // parent_layout did not save for this field, default it to first layout
                            $flex_fields[$fk]['parent_layout'] = $layout['key'];
                        }
                        // append sub field to layout,
                        if ($flex_fields[$fk]['parent_layout'] == $layout['key']) {
                            $layout['sub_fields'][] = acf_extract_var($flex_fields, $fk);
                        }
                    }
                }
                // append back to layouts
                $this->data['field']['layouts'][$fi] = $layout;
            }
        }
    }

    /**
     * @return array
     */
    private function getFieldData(){

        $data = array();

        $field = $this->getData('field');
        $post  = $this->getData('post');

        // set field default values
        $reset = array('multiple', 'class', 'id');
        foreach ($reset as $key){
            if (empty($field[$key])) $field[$key] = false;
        }
        $data['current_field'] = empty($post['fields'][$field['key']]) ? false : $post['fields'][$field['key']];
        $options = array('is_multiple_field_value', 'multiple_value');
        foreach ($options as $option){
            $data['current_' . $option] = isset($field['key']) && isset($post[$option][$field['key']]) ? $post[$option][$field['key']] : false;
        }

        // If parent field exists, parse field name
        if ( "" != $this->getData('field_name') ){

            $field_keys = str_replace(array('[',']'), array(''), str_replace('][', ':', $this->getData('field_name')));

			$data['current_field'] = false;
            foreach (explode(":", $field_keys) as $n => $key) {
	            if (!empty($post['fields'][$key])) {
		            $data['current_field'] = $post['fields'][$key];
	            } elseif (isset($data['current_field'][$key])) {
		            $data['current_field'] = $data['current_field'][$key];
	            }

                foreach ($options as $option){
                    if (!empty($post[$option][$key])) {
                        $data['current_' . $option] = $post[$option][$key];
                    } elseif(!empty($data['current_' . $option][$key])) {
                        $data['current_' . $option] = $data['current_' . $option][$key];
                    }
                }
            }

            $data['current_field'] = empty($data['current_field'][$field['key']]) ? false : $data['current_field'][$field['key']];

            foreach ($options as $option){
                $data['current_' . $option] = isset($data['current_' . $option][$field['key']]) ? $data['current_' . $option][$field['key']] : false;
            }
        }
        return $data;
    }

    /**
     * @param $xpath
     * @param $parsingData
     * @param array $args
     * @return void
     */
    public function parse($xpath, $parsingData, $args = array()) {

        $this->parsingData = $parsingData;

        $defaults = array(
            'field_path' => '',
            'xpath_suffix' => '',
            'repeater_count_rows' => 0,
            'inside_repeater' => false
        );

        $args = array_merge($defaults, $args);

        $field = $this->getData('field');

        $isMultipleField = (isset($parsingData['import']->options['is_multiple_field_value'][$field['key']])) ? $parsingData['import']->options['is_multiple_field_value'][$field['key']] : FALSE;
        $multipleValue   = (isset($parsingData['import']->options['multiple_value'][$field['key']])) ? $parsingData['import']->options['multiple_value'][$field['key']] : FALSE;

        if ("" != $args['field_path']) {

            $fieldKeys = preg_replace('%[\[\]]%', '', str_replace('][', ':', $args['field_path']));
            $is_multiple_field_value = $parsingData['import']->options['is_multiple_field_value'];
            $is_multiple_value = $parsingData['import']->options['multiple_value'];

            foreach (explode(":", $fieldKeys) as $n => $key) {
                $xpath = (!$n) ? $parsingData['import']->options['fields'][$key] : $xpath[$key];

                if (!$n && isset($is_multiple_field_value[$key])) {
                    $isMultipleField = $is_multiple_field_value[$key];
                }
                if (isset($isMultipleField[$key])) {
                    $isMultipleField = $isMultipleField[$key];
                }

                if (!$n && isset($is_multiple_value[$key])) {
                    $multipleValue = $is_multiple_value[$key];
                }
                if (isset($multipleValue[$key])) {
                    $multipleValue = $multipleValue[$key];
                }
            }

            $xpath = empty($xpath[$field['key']]) ? false : $xpath[$field['key']];
            $isMultipleField = isset($isMultipleField[$field['key']]) ? $isMultipleField[$field['key']] : false;
            $multipleValue = isset($multipleValue[$field['key']]) ? $multipleValue[$field['key']] : false;
        }

        $this->setOption('base_xpath', $parsingData['xpath_prefix'] . $parsingData['import']->xpath . $args['xpath_suffix']);
        $this->setOption('xpath', $xpath);
        $this->setOption('is_multiple_field', $isMultipleField);
        $this->setOption('multiple_value', $multipleValue);
        $this->setOption('count', ($args['repeater_count_rows']) ? $args['repeater_count_rows'] : $parsingData['count']);
        $this->setOption('values', array_fill(0, $this->getOption('count'), ""));
        $this->setOption('field_path', $args['field_path']);
    }

    /**
     * @param $importData
     * @param array $args
     * @return bool
     */
    public function import($importData, $args = array()){

        $defaults = array(
            'container_name' => '',
            'parent_repeater' => ''
        );

        $field = $this->getData('field');

        $args = array_merge($defaults, $args);

        $this->importData = array_merge($importData, $args);

        $this->parsingData['logger'] and call_user_func($this->parsingData['logger'], sprintf(__('- Importing field `%s`', 'wp_all_import_acf_add_on'), $this->importData['container_name'] . $field['name']));

        $parsedData = $this->getParsedData();

        // If update is not allowed
        if (!empty($this->importData['articleData']['ID']) && ! \pmai_is_acf_update_allowed($this->importData['container_name'] . $field['name'], $this->parsingData['import']->options, $this->parsingData['import']->id)) {
            $this->parsingData['logger'] && call_user_func($this->parsingData['logger'], sprintf(__('- Field `%s` is skipped attempted to import options', 'wp_all_import_acf_add_on'), $this->getFieldName()));
            return FALSE;
        }

        switch ($this->getImportType()) {
            case 'import_users':
                update_user_meta($this->getPostID(), "_" . $this->getFieldName(), $this->getFieldKey());
                break;
            case 'taxonomies':
                update_term_meta($this->getPostID(), "_" . $this->getFieldName(), $this->getFieldKey());
                break;
            default:
                update_post_meta($this->getPostID(), "_" . $this->getFieldName(), $this->getFieldKey());
                break;
        }

        return TRUE;
    }

    /**
     * @param $importData
     */
    public function saved_post($importData){}

    /**
     *  Render field
     */
    public function view() {
        $this->renderHeader();
        extract($this->data);
        $fields = $this->getSubFields();
        switch ($this->supportedVersion){
            case 'v4':
            case 'v5':
                $fieldDir = PMAI_FIELDS_ROOT_DIR . '/views/'. $this->type;
                $filePath = $fieldDir . DIRECTORY_SEPARATOR . $this->type . '-' . $this->supportedVersion . '.php';
                if (is_file($filePath)) {
                    // Render field header.
                    $header = $fieldDir . DIRECTORY_SEPARATOR . 'header.php';
                    if (file_exists($header) && is_readable($header)) {
                        include $header;
                    }
                    // Render field.
                    include $filePath;
                    // Render field footer.
                    $footer = $fieldDir . DIRECTORY_SEPARATOR . 'footer.php';
                    if (file_exists($footer) && is_readable($footer)) {
                        include $footer;
                    }
                }
                break;
            default:
                $filePath = __DIR__ . '/views/'. $this->type .'.php';
                if (is_file($filePath)) {
                    include $filePath;
                }
                break;
        }
        $this->renderFooter();
    }

        /**
         *  Render field header
         */
        protected function renderHeader(){
            $filePath = __DIR__ . '/templates/header.php';
            if (is_file($filePath)) {
                extract($this->data);
                include $filePath;
            }
        }

        /**
         *  Render field footer
         */
        protected function renderFooter(){
            $filePath = __DIR__ . '/templates/footer.php';
            if (is_file($filePath)) {
                include $filePath;
            }
        }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return \wpai_acf_add_on\acf\fields\Field|bool
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @param \wpai_acf_add_on\acf\fields\Field|bool $parent
     */
    public function setParent($parent) {
        $this->parent = $parent;
    }

    /**
     * @param $option
     * @return bool|mixed
     */
    public function getData($option){
        return isset($this->data[$option]) ? $this->data[$option] : false;
    }

    /**
     * @param $option
     * @param $value
     */
    public function setData($option, $value){
        $this->data[$option] = $value;
    }

    /**
     * @param $option
     * @return bool|mixed
     */
    public function getOption($option){
        return isset($this->options[$option]) ? $this->options[$option] : false;
    }

    /**
     * @param $option
     * @param $value
     */
    public function setOption($option, $value){
        $this->options[$option] = $value;
    }

	/**
	 * @param $xpath
	 * @param string $suffix
	 *
	 * @return array
	 * @throws \XmlImportException
	 */
    public function getByXPath($xpath, $suffix = '') {
        $values = array_fill(0, $this->getOption('count'), "");
        if ($xpath != ""){
            $file = false;
            $values = \XmlImportParser::factory($this->parsingData['xml'], $this->getOption('base_xpath') . $suffix, $xpath, $file)->parse();
            @unlink($file);
        }
        return $values;
    }

    /**
     * @return mixed
     */
    public function getParsingData() {
        return $this->parsingData;
    }

    /**
     * @return mixed
     */
    public function getImportData() {
        return $this->importData;
    }

    /**
     * @return mixed
     */
    public function getPostIndex(){
        return $this->importData['i'];
    }

    /**
     * @return mixed
     */
    public function getPostID(){
        return $this->importData['pid'];
    }

    /**
     * @return string
     */
    public function getFieldName(){
        $fieldName = ( isset($this->data['field']['name']) ? $this->data['field']['name'] : '' );
        if (empty($fieldName)) {
            if (function_exists('_acf_get_field_by_id')) {
                $field = _acf_get_field_by_id($this->data['field']['ID']);
            } else {
                $label = sanitize_title( $this->data['field']['label'] );
	            $fieldName = str_replace('-', '_', $label);
            }

            if (!empty($field)) {
                $fieldName = $this->data['field']['name'] = $field['name'];
            }
        }
        return !isset($this->importData['container_name']) ? $fieldName : $this->importData['container_name'] . $fieldName;
    }

    /**
     * @param $fieldName
     */
    public function setFieldInputName($fieldName){
        $this->data['field_name'] = $fieldName;
        $this->data = array_merge($this->data, $this->getFieldData());
    }

    /**
     * @return string
     */
    public function getFieldKey(){
        return $this->data['field']['key'];
    }

    /**
     * @return string
     */
    public function getFieldLabel(){
        return $this->data['field']['label'];
    }

    /**
     * @return mixed
     */
    public function getFieldValue(){
        $values = $this->options['values'];
        if (isset($this->options['is_multiple_field']) && $this->options['is_multiple_field'] == 'yes') {
            $value = array_shift($values);
        }
        else {
            $value = isset($values[$this->getPostIndex()]) ? $values[$this->getPostIndex()] : '';
            $parents = $this->getParents();
            if (!empty($parents)){
                foreach ($parents as $key => $parent) {
                    if ($parent['delimiter'] !== FALSE) {
                        $value = explode($parent['delimiter'], $value);
                        $value = isset($value[$parent['index']]) ? $value[$parent['index']] : '';
                    }
                }
            }
        }
        return is_array($value) ? array_map('trim', $value) : trim($value);
    }

    /**
     * @param $option
     * @return null|mixed
     */
    public function getFieldOption($option){
        return isset($this->data['field'][$option]) ? $this->data['field'][$option] : NULL;
    }

    /**
     * @param $option
     * @return null|mixed
     */
    public function getImportOption($option){
        $importData = $this->getImportData();
        return isset($importData['import']->options[$option]) ? $importData['import']->options[$option] : NULL;
    }

    /**
     * @return mixed
     */
    public function getImportType(){
        $importData = $this->getImportData();
        return $importData['import']->options['custom_type'];
    }

    /**
     * @return mixed
     */
    public function getTaxonomyType(){
        $importData = $this->getImportData();
        return $importData['import']->options['taxonomy_type'];
    }

    /**
     * @return mixed
     */
    public function getLogger(){
        return $this->parsingData['logger'];
    }

    /**
     * @return array
     */
    public function getSubFields(){
        return $this->subFields;
    }

    /**
     * @return bool
     */
    public function isLocalFieldStorage(){
        return !is_numeric($this->getFieldOption('ID')) || $this->getFieldOption('ID') == 0;
    }

    /**
     * @return array
     */
    public function getDBSubFieldsData(){
        $fieldID = $this->getFieldOption('ID');
        if (empty($fieldID)){
            $fieldData = $this->getDBFieldDataByKey($this->getFieldKey());
            if (!empty($fieldData['ID'])){
                $fieldID = $fieldData['ID'];
            }
        }
        return get_posts(array(
            'posts_per_page' => -1,
            'post_type'      => 'acf-field',
            'post_parent'    => $fieldID,
            'post_status'    => 'publish'
        ));
    }

    /**
     * @return array
     */
    public function getLocalSubFieldsData(){

        $subFieldsData = array();

        $subFields = $this->getFieldOption('sub_fields');

        if (empty($subFields)){

            if (ACFService::isACFNewerThan('5.0.0')) {
                $fields = [];
                if (function_exists('acf_local')) {
                    $fields = acf_local()->fields;
                }
                if (empty($fields) && function_exists('acf_get_local_fields')) {
                    $fields = acf_get_local_fields();
                }
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        if (isset($field['parent']) && $field['parent'] == $this->getFieldKey()) {
                            $subFieldData = $field;
                            $subFieldData['ID'] = $subFieldData['id'] = uniqid();
                            $subFieldsData[] = $subFieldData;
                        }
                    }
                }
            }
            else{

                global $acf_register_field_group;

                if (!empty($acf_register_field_group)){
                    foreach ($acf_register_field_group as $key => $group) {
                        foreach ($group['fields'] as $field) {
                            if (isset($field['parent']) && $field['parent'] == $this->getFieldKey()) {
                                $subFieldData = $field;
                                $subFieldData['ID'] = $subFieldData['id'] = uniqid();
                                $subFieldsData[] = $subFieldData;
                            }
                        }
                    }
                }
            }
        }
        else {
            foreach ($subFields as $field) {
                $subFieldData = $field;
                $subFieldData['ID'] = $subFieldData['id'] = uniqid();
                $subFieldsData[] = $subFieldData;
            }
        }
        return $subFieldsData;
    }

    /**
     * @param $fieldKey
     * @return \wpai_acf_add_on\acf\fields\Field|bool
     */
    public function getFieldByKey($fieldKey){

        // Get field configuration
        $fieldData = $this->isLocalFieldStorage() ? $this->getLocalFieldDataByKey($fieldKey) : $this->getDBFieldDataByKey($fieldKey);

        return $fieldData ? $this->initDataAndCreateField($fieldData) : false;
    }

    /**
     * @param $fieldKey
     * @return bool
     */
    protected function getLocalFieldDataByKey($fieldKey){
        $fieldData = false;
        $fields = [];
        if (function_exists('acf_local')) {
            $fields = acf_local()->fields;
        }
        if (empty($fields) && function_exists('acf_get_local_fields')) {
            $fields = acf_get_local_fields();
        }
        if (!empty($fields)) {
            foreach ($fields as $sub_field) {
                if ($sub_field['key'] == $fieldKey) {
                    $fieldData = $sub_field;
                    $fieldData['ID'] = $fieldData['id'] = uniqid();
                    break;
                }
            }
        }
        return $fieldData;
    }

    /**
     * @param $fieldKey
     * @return array|bool|mixed
     */
    protected function getDBFieldDataByKey($fieldKey){
        $fieldData = false;
        $args = array(
            'name' => $fieldKey,
            'post_type' => 'acf-field',
            'post_status' => 'publish',
            'posts_per_page' => 1
        );
        $my_posts = get_posts($args);
        if ($my_posts) {
            $sub_field = $my_posts[0];
            $fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();
            $fieldData['ID'] = $sub_field->ID;
            $fieldData['label'] = $sub_field->post_title;
            $fieldData['key'] = $sub_field->post_name;
        }
        return $fieldData;
    }

    /**
     * @param $subFieldData
     * @return Field
     */
    public function initDataAndCreateField($subFieldData){

        $fieldData = $subFieldData;

        if (is_object($subFieldData)) {
            $fieldData = empty($subFieldData->post_content) ? array() : unserialize($subFieldData->post_content);
            $fieldData['ID']    = $fieldData['id'] = $subFieldData->ID;
            $fieldData['label'] = $subFieldData->post_title;
            $fieldData['key']   = $subFieldData->post_name;
            $fieldData['name']  = $subFieldData->post_excerpt;
        }

        // Create sub field instance
        return FieldFactory::create($fieldData, $this->getData('post'), $this->getFieldName(), $this);
    }

    /**
     * @return bool
     */
    public function isNotEmpty() {
        return (bool) $this->getCountValues();
    }

    /**
     * @return int
     */
    public function getCountValues(){
        $parents = $this->getParents();
        $value = $this->getOriginalFieldValueAsString();
        if (!empty($parents) && !$this->isEmptyValue($value) && !is_array($value)){
            $parentIndex = false;
            foreach ($parents as $key => $parent) {
                if ($parentIndex !== false){
                    $value = $value[$parentIndex];
                }
                if ($parent['delimiter'] !== FALSE) {
                    $value = explode($parent['delimiter'], $value);
                    if (is_array($value)) {
                        $value = array_filter($value);
                    }
                    $parentIndex = $parent['index'];
                }
            }
        }
        return is_array($value) ? count($value) : !$this->isEmptyValue($value);
    }

    /**
     *
     * Helper function to detect is provided field is empty or not
     *
     * @param $value
     * @return bool
     */
    protected function isEmptyValue($value){
        return ( is_null($value) || $value === false || $value === "");
    }

    /**
     * @return mixed
     */
    public function getOriginalFieldValueAsString(){
        $values = $this->options['values'];
        return isset($values[$this->getPostIndex()]) ? $values[$this->getPostIndex()] : '';
    }

    /**
     * @return array
     */
    protected function getParents(){
        $field = $this;
        $parents = array();
        do{
            $parent = $field->getParent();
            if ($parent){
                switch ($parent->type){
                    case 'repeater':
                        if ($parent->getMode() == 'fixed' || $parent->getMode() == 'csv' && $parent->getDelimiter()){
                            $parents[] = array(
                                'delimiter' => $parent->getDelimiter(),
                                'index'     => $parent->getRowIndex()
                            );
                        }
                        break;
                    default:
                        break;
                }
                $field = $parent;
            }
        }
        while($parent);

        return array_reverse($parents);
    }

    /**
     * @return array
     */
    public function getParsedData(){
        $field = $this->getData('field');
        return array(
            'type' => $field['type'],
            'post_type' => isset($field['post_type']) ? $field['post_type'] : FALSE,
            'name' => $field['name'],
            'multiple' => isset($field['multiple']) ? $field['multiple'] : FALSE,
            'values' => $this->getOption('values'),
            'is_multiple' => $this->getOption('is_multiple'),
            'is_variable' => $this->getOption('is_variable'),
            'is_ignore_empties' => $this->getOption('is_ignore_empties'),
            'xpath' => $this->getOption('xpath'),
            'id' => empty($field['ID']) ? $field['id'] : $field['ID']
        );
    }
}